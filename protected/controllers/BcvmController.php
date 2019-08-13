<?php

/**
 * Административная панель */

class BcvmController extends BtController
{
    protected $lkTwigContext = [];

    protected $userId = null;
    protected $userType = null;

    public function beforeAction($action)
    {
        $this->userId = isset(Yii::app()->session['user']['id']) ? Yii::app()->session['user']['id'] : null;
        $this->userType = isset(Yii::app()->session['user']['type']) ? Yii::app()->session['user']['type'] : null;
        if (empty($this->userId) || empty($this->userType)) {
            //отправка на форму авторизации
            Yii::app()->request->redirect('/auth');
        }

        //заполняем значения переменных Twig, которые нужны на любой странице личного кабинета
        try {
            $this->lkTwigContext['userIsAdmin'] = UserService::getInstance()->checkAccessByAdmin($this->userId);
            if ($this->lkTwigContext['userIsAdmin'] === false) {
                throw new PublicMessageException(ErrorEnum::ACCESS_DENIED);
            }
        } catch (PublicMessageException $e) {
            if ($e->getMessage() === ErrorEnum::USER_NOT_FOUND || $e->getMessage() === ErrorEnum::ACCESS_DENIED) {
                echo (new TemplateEngineTwig)->render(
                    'lk/Error.twig', [
                        'errorText' => $e->getMessage(),
                    ]
                );
                Yii::app()->end();
            }

            $this->lkTwigContext['errorText'] = $e->getMessage();
        }


        return parent::beforeAction($action);
    }

    /**
     * Админка
     */
    public function actionIndex()
    {
        try {
            StatisticService::getInstance()->loadMasterCounts();
            StatisticService::getInstance()->loadClientCounts();
            StatisticService::getInstance()->loadSalonCounts();

            echo (new TemplateEngineTwig)->render('Bcvm.twig', [
                'clientCount' => Yii::app()->session['clientCount'],
                'masterCount' => Yii::app()->session['masterCount'],
                'salonCount' => Yii::app()->session['salonCount'],
                'masterProfileCount' => StatisticService::getInstance()->getMasterProfileCounts(),
                'hairdresserCount' => StatisticService::getInstance()->getHairdresserCounts(),
                'vacancyCount' => VacancyService::getInstance()->getCounts(),
                'orderCount' => StatisticService::getInstance()->getOrderCounts(),
                'incomePaySum' => StatisticService::getInstance()->getIncomePaySum(),
            ]);
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/Error.twig', [
                    'errorText' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Список пользователей
     */
    public function actionUsers()
    {
        try {
            $users = UserService::getInstance()->getAllWithoutAdmin();
            echo (new TemplateEngineTwig)->render('bcvm/Users.twig',
                array_merge([
                    'userRusColumns' => ['Номер', 'Тип', 'Имя', 'Телефон', 'Email', 'Дата создания', 'Подтвержден'],
                    'users' => $users
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('bcvm/Users.twig', [
                'errorText' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Список заказов, сделанных самими клиентами
     */
    public function actionClientOrders()
    {
        try {
            $orders = OrderService::getInstance()->getAllOrders(UserTypeEnum::CLIENT);
            echo (new TemplateEngineTwig)->render('bcvm/Orders.twig',
                array_merge([
                    'orderRusColumns' => ['Номер', 'Дата создания', 'Статус', 'Клиент', 'Отклики мастеров', 'Согласованный мастер', 'Отклики салонов', 'Согласованный салон'],
                    'orderEngColumns' => ['id', 'created_at', 'status', 'ownerUser', 'agreeMasterIds', 'master',  'agreeSalonIds', 'salon'],
                    'orders' => $orders
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('bcvm/Orders.twig', [
                'errorText' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Список заказов, сделанных салонами
     */
    public function actionSalonOrders()
    {
        try {
            $orders = OrderService::getInstance()->getAllOrders(UserTypeEnum::SALON);
            echo (new TemplateEngineTwig)->render('bcvm/Orders.twig',
                array_merge([
                    'orderRusColumns' => ['Номер', 'Дата создания', 'Статус', 'Клиент', 'Отклики мастеров', 'Согласованный мастер', 'Отклики салонов', 'Согласованный салон'],
                    'orderEngColumns' => ['id', 'created_at', 'status', 'ownerUser', 'agreeMasterIds', 'master',  'agreeSalonIds', 'salon'],
                    'orders' => $orders
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('bcvm/Orders.twig', [
                'errorText' => $e->getMessage(),
            ]);
        }
    }

    //================================================================

    /**
     * Подтверждение пользователя
     * @return string
     */
    public function actionConfirmPhone()
    {
        $userId = Yii::app()->request->getParam('user-id');

        if (empty($userId)) {
            $this->response('Ошибка: Отсутвуют входные параметры.', true);
            Yii::app()->end();
        }

        try {
            $result = UserService::getInstance()->confirmPhone($userId);

            $this->response($result, false, $userId, false);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка отправки сообщения: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }
}
