<?php

/**
 * Сервис для оплаты услуг сайта
 */
class RoboPayService
{
    private static $roboPayService = null;

    /** var DateFormatter */
    private $dateFormatter;

    /** @var \CDbConnection */
    private $db;

    /**
     * @return RoboPayService
     */
    public static function getInstance()
    {
        if (self::$roboPayService === null) {
            self::$roboPayService = new self();
        }
        return self::$roboPayService;
    }

    public function __construct()
    {
        $this->db = Yii::app()->db;
        $this->dateFormatter = new DateFormatter();
    }

    /**
     * Результат пополнения баланса через робокассу
     *
     * @param CHttpRequest $request post запрос
     * @return string
     */
    public function resultPay($request)
    {
        $invoiceId = IntVal($request->getParam('InvId'));
        $userId = substr($invoiceId, 0, -3);
        $sum = FloatVal($request->getParam('OutSum'));
        $requestSignatureValue = $request->getParam('SignatureValue');

        if (empty($userId) || $sum < 0) {
            return 'ERROR';
        }

        if (ROBO_TEST === 1) {
            $signatureValue = mb_strtoupper(
                md5($sum . ':' . $invoiceId . ':' . ROBO_PASS_TEST_2)
            );
        } else {
            $signatureValue = mb_strtoupper(
                md5(number_format($sum, 6) . ':' . $invoiceId . ':' . ROBO_PASS_2)
            );
        }

        //проверка сигнатуры
        if ($requestSignatureValue !== $signatureValue) {
            BtLogger::getLogger()->error('Invalid Result Pay.', [
                'user_id' => $userId,
                'InvId' => $invoiceId,
                'summ' => $sum,
                'is_test' => $request->getParam('IsTest'),
                'fee' => $request->getParam('Fee'), //комиссия
                'requestSignatureValue' => $requestSignatureValue,
                'siteSignatureValue' => $signatureValue,
            ]);
            return 'ERROR';
        }

        //Пересчет баланса
        if ($this->addBalance($userId, $sum) === false) {
            BtLogger::getLogger()->error('Invalid calc balance.', [
                'user_id' => $userId,
                'InvId' => $invoiceId,
                'summ' => $sum,
                'is_test' => $request->getParam('IsTest'),
                'fee' => $request->getParam('Fee'), //комиссия
                'requestSignatureValue' => $requestSignatureValue,
                'siteSignatureValue' => $signatureValue,
            ]);
            return 'ERROR';
        };

        return 'OK' . $invoiceId;
    }


    /**
     * Пополнение баланса пользователя на сайте
     *
     * @param integer $userId идентификатор пользователя
     * @param float $sum сумма платежа
     * @return bool
     */
    public function addBalance($userId, $sum)
    {
        try {
            $payOperationModel = new PayOperationModel();
            $payOperationModel->user_id = $userId;
            $payOperationModel->sum = $sum;
            $payOperationModel->type_id = PayOperationTypeModel::model()->findByAttributes([
                'name' => PayOperationTypeEnum::ADD_PAYMENT
            ])->id;

            $transaction = $this->db->beginTransaction();
            if ($payOperationModel->save() === false) {
                BtLogger::getLogger()->error('Can not save pay operation.', [
                    'userId' => $userId,
                    'error' => $payOperationModel->getErrors(),
                ]);
                return false;
            }

            (new UserDsp)->calcBalance($userId);
            $transaction->commit();

            return true;
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save pay operation. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            return false;
        }
    }

    /**
     * Абонентская плата за оповещение
     *
     * @param integer $userId идентификатор пользователя
     * @param float $sum сумма абонентской платы
     * @return bool
     */
    public function smsOrderPay($userId, $sum)
    {
        try {
            $transaction = $this->db->beginTransaction();
            $currBalance = $this->getBalance($userId);
            if (($currBalance - $sum) < 0) {
                BtLogger::getLogger()->error('Insufficient funds in the account.', [
                    'userId' => $userId,
                    'currBalance' => $currBalance,
                    'pay_sum' => $sum,
                ]);
                return false;
            }

            $payOperationModel = new PayOperationModel();
            $payOperationModel->user_id = $userId;
            $payOperationModel->sum = (-1) * $sum;
            $payOperationModel->type_id = PayOperationTypeModel::model()->findByAttributes([
                'name' => PayOperationTypeEnum::SMS_PAY
            ])->id;

            if ($payOperationModel->save() === false) {
                BtLogger::getLogger()->error('Can not save pay operation.', [
                    'userId' => $userId,
                    'error' => $payOperationModel->getErrors(),
                ]);
                return false;
            }

            $userModel = UserModel::model()->findByPk($userId);
            $userModel->sms_pay_flag = true;
            if ($userModel->save() === false) {
                BtLogger::getLogger()->error('Can not save sms_pay_flag.', [
                    'userId' => $userId,
                    'error' => $payOperationModel->getErrors(),
                ]);
                return false;
            }

            (new UserDsp)->calcBalance($userId);
            $transaction->commit();

            return true;
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save pay operation. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            return false;
        }
    }

    /**
     * Возвращает баланс пользователя на сайте
     *
     * @param integer $userId идентификатор пользователя
     * @return float
     * @throws PublicMessageException
     */
    public function getBalance($userId)
    {
        $userModel = UserModel::model()->findByPk($userId);
        if ($userModel === null) {
            BtLogger::getLogger()->error('Get balance. User not found.', [
                'userId' => $userId,
            ]);
            throw new PublicMessageException('Пользователь не найден.');
        }
        return $userModel->balance;
    }

    /**
     * Успешное пополнение баланса через робокассу
     *
     * @param CHttpRequest $request post запрос
     * @return bool
     */
    public function successPay($request)
    {
        BtLogger::getLogger()->error('Success Pay.', [
            'request' => $request,
        ]);

        return true;
    }

    /**
     * Неуспешное пополнение баланса через робокассу
     *
     * @param CHttpRequest $request post запрос
     * @return bool
     */
    public function failPay($request)
    {
        BtLogger::getLogger()->error('Fail Pay.', [
            'request' => $request,
        ]);

        return true;
    }

}
