<?php

/**
 * Сервис для отправки сообщений по СМС и email
 */

class SenderService
{
    private static $senderService = null;

    /**
     * @return SenderService
     */
    public static function getInstance()
    {
        if (self::$senderService === null) {
            self::$senderService = new self();
        }
        return self::$senderService;
    }

    /**
     * Отправка мастерам и салонам сообщений о создании заказа
     *
     * @return bool
     */
    public function sendSmsAfterOrderCreate()
    {
        $messageType = 'create_order';
        $message = 'Скорее заходи на ' . SITE_NAME . '.ru/lk/clientOrders там есть клиент у тебя.';

        $orderModels = OrderModel::model()->findAllByAttributes([
            'status' => OrderStatusEnum::CREATED,
            'send_sms_order_create_flag' => false,
        ], [
            'condition' => 'latitude IS NOT NULL AND longitude IS NOT NULL',
        ]);

        foreach ($orderModels as $orderModel) {
            try {
                $masters = (new MasterProfileDsp)->findMasterByOrder(
                    $orderModel->main_service_id,
                    $orderModel->receipt_date,
                    $orderModel->latitude,
                    $orderModel->longitude,
                    $orderModel->near_subway
                );
                foreach ($masters as $master) {
                    if (!empty($master['phone']) && !empty($master['sms_confirm'])) {
                        (new SmsSender)->sendMessage([$master['phone']], $message, $messageType);
                    }
                }

                $salons = (new SalonProfileDsp)->findSalonByOrder(
                    $orderModel->main_service_id,
                    $orderModel->receipt_date,
                    $orderModel->latitude,
                    $orderModel->longitude,
                    $orderModel->near_subway
                );
                foreach ($salons as $salon) {
                    if (!empty($salon['phone']) && !empty($salon['sms_confirm'])) {
                        (new SmsSender)->sendMessage([$salon['phone']], $message, $messageType);
                    }
                }

                $orderModel->send_sms_order_create_flag = true;
                if (!$orderModel->save()) {
                    BtLogger::getLogger()->error('Can not update send_sms_order_create_flag.', [
                        'method' => __METHOD__,
                        'error' => $orderModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Ошибка обновления send_sms_order_create_flag.');
                }
            } catch (Exception $e) {
                BtLogger::getLogger()->error('Can not get master counts or update send_sms_order_create_flag. DB error.',
                    [
                        'method' => __METHOD__,
                        'error' => $e,
                    ]);
            }
        }
    }


    /**
     * Отправка клиенту сообщений об отклике мастера на заказ
     *
     * @param integer $orderId идентификатор заказа
     * @param string $clientPhone телефон клиента
     * @return bool
     */
    public function sendSmsAfterMasterRespondToOrder($orderId, $clientPhone)
    {
        $messageType = 'order_master_respond';
        $message = 'Новый отклик к заданию ' . SITE_NAME . '.ru/lk/order?id=' . $orderId . '. Заходи скорее.';

        try {
            (new SmsSender)->sendMessage([$clientPhone], $message, $messageType);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not sendSmsAfterMasterRespondToOrder. DB error.',
                [
                    'method' => __METHOD__,
                    'error' => $e,
                ]);
            return false;
        }

        return true;
    }

    /**
     * Отправка клиенту сообщений об окончательном выборе мастера в заказе
     *
     * @param integer $orderId идентификатор заказа
     * @param string $clientPhone телефон клиента
     * @param string $masterName имя мастера
     * @param string $masterPhone телефон мастера
     * @return bool
     */
    public function sendSmsAfterMasterSelectedToOrder($orderId, $clientPhone, $masterName, $masterPhone)
    {
        if (empty($orderId)) {
            BtLogger::getLogger()->error('Can not sendSmsAfterMasterSelectedToOrder. Empty order ID.',
                [
                    'method' => __METHOD__,
                ]);

            return false;
        }

        $messageType = 'order_master_selected';
        $message = 'Отличный выбор! ' . $masterName . ' ' . $masterPhone . '. Будем благодарны за отзыв.';

        try {
            (new SmsSender)->sendMessage([$clientPhone], $message, $messageType);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not sendSmsAfterMasterSelectedToOrder. DB error.',
                [
                    'method' => __METHOD__,
                    'error' => $e,
                ]);

            return false;
        }

        return true;
    }
}
