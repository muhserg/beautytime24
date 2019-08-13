<?php

/**
 * Сервис для обработки заказов и заявок
 */
class OrderService
{
    private static $orderService = null;

    /** var DateFormatter */
    private $dateFormatter;

    /** var BoolFormatter */
    private $boolFormatter;

    /** var ImageHelper */
    private $imageHelper;

    /** var CDbConnection */
    private $db;

    /**
     * @return OrderService
     */
    public static function getInstance()
    {
        if (self::$orderService === null) {
            self::$orderService = new self();
        }
        return self::$orderService;
    }

    public function __construct()
    {
        $this->dateFormatter = new DateFormatter();
        $this->boolFormatter = new BoolFormatter();
        $this->db = Yii::app()->db;
        $this->imageHelper = new ImageHelper();
    }

    /**
     * Создание заказа
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     * @param integer $serviceId идентификатор услуги
     * @param string $orderDate дата посещения мастера
     * @param integer $masterBufProfileId идентификатор мастера
     * @param string $addressCoord координаты клиента
     * @param string $description описание заказа
     * @param float $orderCost бюджет заказа
     * @param string $orderPlace где исполнять заказ
     * @param string $orderTime время посещения
     * @param array $orderPhotos фотки заказа
     *
     * @return integer
     * @throws PublicMessageException
     */
    public function create(
        $userId,
        $userTypeName,
        $serviceId,
        $orderDate,
        $masterBufProfileId = null,
        $address = null,
        $addressCoord = null,
        $description = null,
        $orderCost = null,
        $orderPlace = null,
        $orderTime = null,
        $orderPhotos = null
    ) {
        try {
            $orderModel = new OrderModel;
            $orderModel->owner_user_id = $userId;
            $orderModel->owner_user_type_id = UserTypeModel::model()->findByAttributes(['name' => $userTypeName])->id;
            $orderModel->main_service_id = $serviceId;
            if (!empty($orderDate)) {
                if (!empty($orderTime)) {
                    $orderModel->receipt_date = (new DateTime($orderDate))->format('Y-m-d') . ' ' . $orderTime;
                } else {
                    $orderModel->receipt_date = (new DateTime($orderDate))->format('Y-m-d H:i');
                }
            }
            $orderModel->address = $address;
            if (!empty($addressCoord)) {
                $orderModel->address_coord = $addressCoord;

                list($lat, $lng) = explode(',', $addressCoord);
                $orderModel->latitude = deg2rad($lat);
                $orderModel->longitude = deg2rad($lng);
                $orderModel->near_subway = ProfileService::getInstance()->getNearSubway($lat, $lng);
            }

            $orderModel->address_coord = $addressCoord;
            $orderModel->description = $description;
            if ($orderCost > 0) {
                $orderModel->plan_price = $orderCost;
            }
            $orderModel->place = $orderPlace;

            $orderModel->status = OrderStatusEnum::CREATED;
            $orderModel->total = 0;

            //фотки заказа - пока одна
            if (!empty($orderPhotos['tmp_name'])) {
                $orderModel->photo_file_name = $this->imageHelper->uploadImage($orderPhotos, IMG_UPLOAD_ORDER_DIR);
            }

            $transaction = $this->db->beginTransaction();
            if (!$orderModel->save()) {
                BtLogger::getLogger()->error('Can not create order.', [
                    'userId' => $userId,
                    'serviceId' => $serviceId,
                    'masterBufProfileId' => $masterBufProfileId,
                    'error' => $orderModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось создать заказ.');
            }

            //если уже есть мастер, отправляем ему согласование
            if (!empty($masterBufProfileId)) {
                OrderService::getInstance()->bindBufMaster(
                    $userId,
                    $orderModel->id,
                    $masterBufProfileId,
                    true,
                    UserTypeEnum::CLIENT
                );
            }
            $transaction->commit();

            return $orderModel->id;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not create order. Fatal error.', [
                'userId' => $userId,
                'serviceId' => $serviceId,
                'masterBufProfileId' => $masterBufProfileId,
                'error' => $e,
            ]);

            if (strpos($e->getMessage(), 'Failed to parse time string') > 0) {
                throw new PublicMessageException('Неверно введена дата заказа. ');
            }

            throw new PublicMessageException('Не удалось создать заказ. Ошибка в БД.');
        }
    }

    /**
     * Отмена согласования заказа
     *
     * @param string $orderId идентификатор заказа
     * @param integer $userId идентификатор пользователя
     * @param string $initialUserType кто инициатор отмены согласования (тип пользователя инициатора)
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function cancelAgree($orderId, $userId, $initialUserType)
    {
        try {
            $orderModel = OrderModel::model()->findByPk($orderId);
            if ($orderModel === null) {
                throw new PublicMessageException('Заказ не найден.');
            }
            if ($initialUserType === UserTypeEnum::CLIENT && $orderModel->owner_user_id !== $userId) {
                throw new PublicMessageException('Заказ не принадлежит данному пользователю.');
            }

            $initialModel = ProfileService::getInstance()->getProfileModel(null, $initialUserType, $userId);
            if ($initialModel === null) {
                throw new PublicMessageException('Профиль инициатора не найден.');
            }

            $updateAgreeParam = null;
            if ($initialUserType === UserTypeEnum::CLIENT) {
                $updateAgreeParam = ['client_agree' => false];
            } elseif ($initialUserType === UserTypeEnum::MASTER) {
                $updateAgreeParam = ['master_agree' => false];
            } elseif ($initialUserType === UserTypeEnum::SALON) {
                $updateAgreeParam = ['salon_agree' => false];
            }

            $isCommitInThisFunc = false;
            if ($this->db->getCurrentTransaction() === null) {
                $isCommitInThisFunc = true;
                $transaction = $this->db->beginTransaction();
            }

            if ($initialUserType === UserTypeEnum::CLIENT || $initialUserType === UserTypeEnum::MASTER) {
                BufMasterOrderModel::model()->updateAll($updateAgreeParam, [
                    'condition' => 'profile_id = :profile_id AND order_id = :order_id',
                    'params' => [
                        'profile_id' => $initialModel->id,
                        'order_id' => $orderId,
                    ],
                ]);
            }

            if ($initialUserType === UserTypeEnum::CLIENT || $initialUserType === UserTypeEnum::SALON) {
                BufSalonOrderModel::model()->updateAll($updateAgreeParam, [
                    'condition' => 'profile_id = :profile_id AND order_id = :order_id',
                    'params' => [
                        'profile_id' => $initialModel->id,
                        'order_id' => $orderId,
                    ],
                ]);
            }

            //если заказ до этого был уже взаимно согласован
            if ($orderModel->master_profile_id !== null || $orderModel->salon_profile_id !== null) {
                $orderModel->status = OrderStatusEnum::SEND_TO_AGREE;
            } else {
                $orderModel->status = OrderStatusEnum::CREATED;
            }

            $orderModel->master_profile_id = null;
            $orderModel->salon_profile_id = null;

            if (!$orderModel->save()) {
                BtLogger::getLogger()->error('Can not create order.', [
                    'userId' => $userId,
                    'orderId' => $orderId,
                    'error' => $orderModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось отменить согласование заказа.');
            }
            if ($isCommitInThisFunc === true) {
                $transaction->commit();
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not cancel agree order. Fatal error.', [
                'userId' => $userId,
                'orderId' => $orderId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось отменить согласование заказа. Ошибка в БД.');
        }
    }


    /**
     * Отметка о выполнении заказа
     *
     * @param string $orderId идентификатор заказа
     * @param integer $userId идентификатор пользователя
     * @param integer $userType тип пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function execute($orderId, $userId, $userType)
    {
        try {
            $orderModel = OrderModel::model()->findByPk($orderId);
            if ($orderModel === null) {
                throw new PublicMessageException('Заказ не найден.');
            }
            if ($userType === UserTypeEnum::CLIENT) {
                throw new PublicMessageException('Клиент не может выполнить заказ.');
            }

            $orderModel->status = OrderStatusEnum::DONE;

            if (!$orderModel->save()) {
                BtLogger::getLogger()->error('Can not execute order.', [
                    'userId' => $userId,
                    'orderId' => $orderId,
                    'error' => $orderModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось отметить выполнение заказа.');
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not execute order. Fatal error.', [
                'userId' => $userId,
                'orderId' => $orderId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось отметить выполнение заказа. Ошибка в БД.');
        }
    }


    /**
     * Отметка об оценке заказа и возврат самого заказа
     *
     * @param string $orderId идентификатор заказа
     * @param integer $userId идентификатор пользователя
     *
     * @return OrderModel
     * @throws PublicMessageException
     */
    public function assessment($orderId, $userId)
    {
        try {
            $orderModel = OrderModel::model()->findByPk($orderId);
            if ($orderModel === null) {
                throw new PublicMessageException('Заказ не найден.');
            }

            $orderModel->status = OrderStatusEnum::ASSESSMENT;

            if (!$orderModel->save()) {
                BtLogger::getLogger()->error('Can not to assessment order.', [
                    'userId' => $userId,
                    'orderId' => $orderId,
                    'error' => $orderModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось обновить статус заказа на "оценен".');
            }

            return $orderModel;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not to assessment order. Fatal error.', [
                'userId' => $userId,
                'orderId' => $orderId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось обновить статус заказа на "оценен". Ошибка в БД.');
        }
    }


    /**
     * Привязка предварительного мастера к заказу или окончательное согласование мастера
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $orderId идентификатор заказа
     * @param integer $masterProfileId идентификатор мастера
     * @param boolean $isAgree согласовать или не согласовывать
     * @param string $initialOrAgreement кто инициатор (клиент, мастер, салон) или кто окончательно согласовывает (клиент, мастер, салон)
     *
     * @return integer
     * @throws PublicMessageException
     */
    public function bindBufMaster(
        $userId,
        $orderId,
        $masterProfileId,
        $isAgree,
        $initialOrAgreement
    ) {
        try {
            $orderModel = OrderModel::model()->findByPk($orderId);
            if ($orderModel === null) {
                throw new PublicMessageException('Заказ не найден.');
            }
            if ($initialOrAgreement === UserTypeEnum::CLIENT && $orderModel->owner_user_id !== $userId) {
                throw new PublicMessageException('Заказ не принадлежит данному пользователю.');
            }
            if ($orderModel->master_profile_id !== null) {
                throw new PublicMessageException('Заказ уже согласован обоими сторонами.');
            }
            $masterModel = MasterProfileModel::model()->findByPk($masterProfileId);
            if (empty($masterModel)) {
                throw new PublicMessageException('Мастер не найден.');
            }

            $masterBufModel = BufMasterOrderModel::model()->findByPk([
                'profile_id' => $masterProfileId,
                'order_id' => $orderId,
            ]);

            //привязка предварительного мастера к заказу
            if (empty($masterBufModel)) {
                $masterBufModel = new BufMasterOrderModel;
                $masterBufModel->profile_id = $masterProfileId;
                $masterBufModel->order_id = $orderId;

                if ($initialOrAgreement === UserTypeEnum::CLIENT) {
                    $masterBufModel->client_agree = $isAgree;
                    $masterBufModel->master_agree = false;
                } else {
                    $masterBufModel->client_agree = false;
                    $masterBufModel->master_agree = $isAgree;
                }
                //окончательное согласование мастера
            } else {
                if ($initialOrAgreement === UserTypeEnum::CLIENT) {
                    $masterBufModel->client_agree = $isAgree;
                } else {
                    $masterBufModel->master_agree = $isAgree;
                }

                $masterBufModel->client_agree = $this->boolFormatter->format($masterBufModel->client_agree);
                $masterBufModel->master_agree = $this->boolFormatter->format($masterBufModel->master_agree);
            }

            $isCommitInThisFunc = false;
            if ($this->db->getCurrentTransaction() === null) {
                $isCommitInThisFunc = true;
                $transaction = $this->db->beginTransaction();
            }

            if (!$masterBufModel->save()) {
                BtLogger::getLogger()->error('Can not bind buf master to order.', [
                    'userId' => $userId,
                    'orderId' => $orderId,
                    'masterId' => $masterProfileId,
                    'error' => $orderModel->getErrors(),
                ]);

                throw new PublicMessageException('Ошибка сохранения предварительного мастера для заказа.');
            }

            //Окончательно привязываем мастера к заказу
            if ($masterBufModel->client_agree === true && $masterBufModel->master_agree === true) {
                $this->bindMaster($userId, $orderId, $masterProfileId);
            } else {
                if ($masterBufModel->client_agree === false && $masterBufModel->master_agree === false) {
                    $orderModel->status = OrderStatusEnum::CREATED;
                } else {
                    $orderModel->status = OrderStatusEnum::SEND_TO_AGREE;
                }
                if (!$orderModel->save()) {
                    BtLogger::getLogger()->error('Can not save status to order.', [
                        'userId' => $userId,
                        'orderId' => $orderId,
                        'masterId' => $masterProfileId,
                        'error' => $orderModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Ошибка сохранения статуса заказа.');
                }
            }

            if ($isCommitInThisFunc === true) {
                $transaction->commit();
            }

            //отправка смс клиенту
            $ownerOrderModel = UserModel::model()->findByPk($orderModel->owner_user_id);
            if ($masterBufModel->client_agree === true && $masterBufModel->master_agree === true) {
                SenderService::getInstance()->sendSmsAfterMasterSelectedToOrder(
                    $orderModel->id,
                    $ownerOrderModel->phone,
                    $masterModel->first_name,
                    $masterModel->phone
                );
            } elseif ($initialOrAgreement === UserTypeEnum::MASTER && $masterBufModel->master_agree === true) {
                SenderService::getInstance()->sendSmsAfterMasterRespondToOrder(
                    $orderModel->id,
                    $ownerOrderModel->phone
                );
            }

            return $masterBufModel->profile_id;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not bind buf master to order. Fatal error.', [
                'userId' => $userId,
                'orderId' => $orderId,
                'masterId' => $masterProfileId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Ошибка сохранения предварительного мастера для заказа. Ошибка в БД.');
        }
    }

    /**
     * Привязка предварительного салону к заказу или окончательное согласование салона
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $orderId идентификатор заказа
     * @param integer $salonProfileId идентификатор салона
     * @param boolean $isAgree согласовать или не согласовывать
     * @param string $initialOrAgreement кто инициатор - клиент или мастер или кто окончательно согласовывает
     *
     * @return integer
     * @throws PublicMessageException
     */
    public function bindBufSalon(
        $userId,
        $orderId,
        $salonProfileId,
        $isAgree,
        $initialOrAgreement
    ) {
        try {
            $orderModel = OrderModel::model()->findByPk($orderId);
            if ($orderModel === null) {
                throw new PublicMessageException('Заказ не найден.');
            }
            if ($initialOrAgreement === UserTypeEnum::CLIENT && $orderModel->owner_user_id !== $userId) {
                throw new PublicMessageException('Заказ не принадлежит данному пользователю.');
            }
            if ($orderModel->salon_profile_id !== null) {
                throw new PublicMessageException('Заказ уже согласован обоими сторонами.');
            }
            $salonModel = SalonProfileModel::model()->findByPk($salonProfileId);
            if (empty($salonModel)) {
                throw new PublicMessageException('Салон не найден.');
            }

            $salonBufModel = BufSalonOrderModel::model()->findByPk([
                'profile_id' => $salonProfileId,
                'order_id' => $orderId,
            ]);

            //привязка предварительного мастера к заказу
            if (empty($salonBufModel)) {
                $salonBufModel = new BufSalonOrderModel;
                $salonBufModel->profile_id = $salonProfileId;
                $salonBufModel->order_id = $orderId;

                if ($initialOrAgreement === UserTypeEnum::CLIENT) {
                    $salonBufModel->client_agree = $isAgree;
                    $salonBufModel->salon_agree = false;
                } else {
                    $salonBufModel->client_agree = false;
                    $salonBufModel->salon_agree = $isAgree;
                }
                //окончательное согласование мастера
            } else {
                if ($initialOrAgreement === UserTypeEnum::CLIENT) {
                    $salonBufModel->client_agree = $isAgree;
                } else {
                    $salonBufModel->salon_agree = $isAgree;
                }

                $salonBufModel->client_agree = $this->boolFormatter->format($salonBufModel->client_agree);
                $salonBufModel->salon_agree = $this->boolFormatter->format($salonBufModel->salon_agree);
            }

            $isCommitInThisFunc = false;
            if ($this->db->getCurrentTransaction() === null) {
                $isCommitInThisFunc = true;
                $transaction = $this->db->beginTransaction();
            }

            if (!$salonBufModel->save()) {
                BtLogger::getLogger()->error('Can not bind buf salon to order.', [
                    'userId' => $userId,
                    'orderId' => $orderId,
                    'salonId' => $salonProfileId,
                    'error' => $orderModel->getErrors(),
                ]);

                throw new PublicMessageException('Ошибка сохранения предварительного мастера для заказа.');
            }

            //Окончательно привязываем мастера к заказу
            if ($salonBufModel->client_agree === true && $salonBufModel->salon_agree === true) {
                $this->bindSalon($userId, $orderId, $salonProfileId);
            } else {
                if ($salonBufModel->client_agree === false && $salonBufModel->salon_agree === false) {
                    $orderModel->status = OrderStatusEnum::CREATED;
                } else {
                    $orderModel->status = OrderStatusEnum::SEND_TO_AGREE;
                }
                if (!$orderModel->save()) {
                    BtLogger::getLogger()->error('Can not save status to order.', [
                        'userId' => $userId,
                        'orderId' => $orderId,
                        'salonId' => $salonProfileId,
                        'error' => $orderModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Ошибка сохранения статуса заказа.');
                }
            }
            if ($isCommitInThisFunc === true) {
                $transaction->commit();
            }

            return $salonBufModel->profile_id;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not bind buf salon to order. Fatal error.', [
                'userId' => $userId,
                'orderId' => $orderId,
                'salonId' => $salonProfileId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Ошибка сохранения предварительного салона для заказа. Ошибка в БД.');
        }
    }


    /**
     * Удаление заказа
     *
     * @param string $orderId идентификатор заказа
     * @param integer $userId идентификатор пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function delete($orderId, $userId)
    {
        $orderModel = OrderModel::model()->findByPk($orderId);
        if ($orderModel === null) {
            throw new PublicMessageException('Заказ не найден.');
        }
        if ($orderModel->owner_user_id !== $userId) {
            throw new PublicMessageException('Заказ не принадлежит данному пользователю.');
        }

        try {
            $orderModel->status = OrderStatusEnum::DELETED;

            if (!$orderModel->save()) {
                BtLogger::getLogger()->error('Can not delete order.', [
                    'userId' => $userId,
                    'orderId' => $orderId,
                    'error' => $orderModel->getErrors(),
                ]);

                throw new PublicMessageException('Ошибка удаления заказа.');
            }
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not delete order. Fatal error.', [
                'userId' => $userId,
                'orderId' => $orderId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Ошибка удаления заказа. Ошибка в БД.');
        }
    }


    /**
     * Проверка полного согласования заказа
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $orderId идентификатор заказа
     * @param integer $masterProfileId идентификатор мастера
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function checkAgree(
        $userId,
        $orderId,
        $masterProfileId
    ) {
        try {
            $masterBufModel = BufMasterOrderModel::model()->findByAttributes([
                'profile_id' => $masterProfileId,
                'order_id' => $orderId,
                'master_agree' => true,
                'client_agree' => true,
            ]);

            //привязка предварительного мастера к заказу
            if (!empty($masterBufModel)) {
                return true;
            } else {
                return false;
            }
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not check agree to order. Fatal error.', [
                'userId' => $userId,
                'orderId' => $orderId,
                'masterId' => $masterProfileId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Ошибка проверки полного согласования для заказа. Ошибка в БД.');
        }
    }

    /**
     * Привязка мастера к заказу
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $orderId идентификатор заказа
     * @param integer $masterProfileId идентификатор мастера
     *
     * @return string
     * @throws PublicMessageException
     */
    public function bindMaster(
        $userId,
        $orderId,
        $masterProfileId
    ) {
        try {
            $orderModel = OrderModel::model()->findByPk($orderId);
            if (empty($orderModel)) {
                throw new PublicMessageException('Заказ не существует.');
            }
            $masterModel = MasterProfileModel::model()->findByPk($masterProfileId);
            if (empty($masterModel)) {
                throw new PublicMessageException('Мастер не найден.');
            }

            $masterServiceModel = SalonProfileServiceModel::model()->findByPk([
                'profile_id' => $masterProfileId,
                'service_id' => $orderModel->main_service_id,
            ]);

            $orderModel->status = OrderStatusEnum::AGREE;
            $orderModel->master_profile_id = $masterProfileId;
            if (!empty($masterServiceModel->cost) && $masterServiceModel->cost > 0) {
                $orderModel->total = $masterServiceModel->cost;
            }

            if (!$orderModel->save()) {
                BtLogger::getLogger()->error('Can not bind master to order.', [
                    'userId' => $userId,
                    'orderId' => $orderId,
                    'masterId' => $masterProfileId,
                    'error' => $orderModel->getErrors(),
                ]);

                throw new PublicMessageException('Ошибка выбора мастера для заказа.');
            }

            return $masterModel->last_name . ' ' . $masterModel->first_name;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not bind master to order. Fatal error.', [
                'userId' => $userId,
                'orderId' => $orderId,
                'masterId' => $masterProfileId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Ошибка выбора мастера для заказа. Ошибка в БД.');
        }
    }


    /**
     * Привязка салона к заказу
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $orderId идентификатор заказа
     * @param integer $salonProfileId идентификатор мастера
     *
     * @return string
     * @throws PublicMessageException
     */
    public function bindSalon(
        $userId,
        $orderId,
        $salonProfileId
    ) {
        try {
            $orderModel = OrderModel::model()->findByPk($orderId);
            if (empty($orderModel)) {
                throw new PublicMessageException('Заказ не существует.');
            }
            $salonModel = SalonProfileModel::model()->findByPk($salonProfileId);
            if (empty($salonModel)) {
                throw new PublicMessageException('Салон не найден.');
            }

            $salonServiceModel = SalonProfileServiceModel::model()->findByPk([
                'profile_id' => $salonProfileId,
                'service_id' => $orderModel->main_service_id,
            ]);

            $orderModel->status = OrderStatusEnum::AGREE;
            $orderModel->salon_profile_id = $salonProfileId;
            if ($salonServiceModel->cost > 0) {
                $orderModel->total = $salonServiceModel->cost;
            }

            if (!$orderModel->save()) {
                BtLogger::getLogger()->error('Can not bind salon to order.', [
                    'userId' => $userId,
                    'orderId' => $orderId,
                    'salonId' => $salonProfileId,
                    'error' => $orderModel->getErrors(),
                ]);

                throw new PublicMessageException('Ошибка выбора салона для заказа.');
            }

            return $salonModel->gendir_last_name . ' ' . $salonModel->gendir_first_name;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not bind salon to order. Fatal error.', [
                'userId' => $userId,
                'orderId' => $orderId,
                'masterId' => $salonProfileId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Ошибка выбора салона для заказа. Ошибка в БД.');
        }
    }

    /**
     * Создание предварительного заказа
     *
     * @param string $session_php_id идентификатор сессии
     * @param integer $serviceId идентификатор услуги
     * @param string $orderDate дата посещения мастера
     * @param string $address адоес клиента
     * @param string $addressCoord координаты клиента
     * @param string $description описание заказа
     * @param float $orderCost бюджет заказа
     * @param string $orderPlace где исполнять заказ
     * @param string $orderTime время посещения
     * @param array $orderPhotos фотки заказа
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function createBuf(
        $session_php_id,
        $serviceId,
        $orderDate,
        $address,
        $addressCoord,
        $description,
        $orderCost,
        $orderPlace,
        $orderTime,
        $orderPhotos
    ) {
        try {
            $orderModel = new BufOrderModel;
            $orderModel->owner_session_php_id = $session_php_id;
            $orderModel->main_service_id = $serviceId;
            if (!empty($orderDate)) {
                if (!empty($orderTime)) {
                    $orderModel->receipt_date = (new DateTime($orderDate))->format('Y-m-d') . ' ' . $orderTime;
                } else {
                    $orderModel->receipt_date = (new DateTime($orderDate))->format('Y-m-d H:i');
                }
            }
            $orderModel->address = $address;
            if (!empty($addressCoord)) {
                $orderModel->address_coord = $addressCoord;

                list($lat, $lng) = explode(',', $addressCoord);
                $orderModel->latitude = deg2rad($lat);
                $orderModel->longitude = deg2rad($lng);
                $orderModel->near_subway = ProfileService::getInstance()->getNearSubway($lat, $lng);
            }

            $orderModel->description = $description;
            if ($orderCost > 0) {
                $orderModel->plan_price = $orderCost;
            }
            $orderModel->place = $orderPlace;
            $orderModel->total = 0;

            //фотки заказа - пока одна
            if (!empty($orderPhotos['tmp_name'])) {
                $orderModel->photo_file_name = $this->imageHelper->uploadImage($orderPhotos, IMG_UPLOAD_ORDER_DIR);
            }

            if (!$orderModel->save()) {
                BtLogger::getLogger()->error('Can not create buf order.', [
                    'session_php_id' => $session_php_id,
                    'serviceId' => $serviceId,
                    'error' => $orderModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось создать предварительный заказ.');
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not create buf order. Fatal error.', [
                'session_php_id' => $session_php_id,
                'serviceId' => $serviceId,
                'error' => $e,
            ]);

            if (strpos($e->getMessage(), 'Failed to parse time string') > 0) {
                throw new PublicMessageException('Неверно введена дата заказа. ');
            }

            throw new PublicMessageException('Не удалось создать предварительный  заказ. Ошибка в БД.');
        }
    }

    /**
     * Список заказов созданных данным пользователем
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getMyOrders($userId, $userTypeName)
    {
        try {
            $orderModels = OrderModel::model()->with('client')->with('service')->with('master')->with('salon')
                ->findAllByAttributes([
                    'owner_user_id' => $userId,
                ], [
                    'order' => '`t`.created_at DESC',
                ]);
            if ($orderModels === null) {
                return [];
            }

            return $this->getFullArrayOrder($orderModels, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get orders. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список заказов. Ошибка в БД.');
        }
    }


    /**
     * Список заказов, привязанных к данному мастеру (все заказы)
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getOrdersByMaster($userId, $userTypeName)
    {
        try {
            $masterModel = MasterProfileModel::model()->findByAttributes(['user_id' => $userId]);
            if ($masterModel === null) {
                return [];
            }
            $orders = OrderModel::model()->with('client')->with('master')->findAllByAttributes([
                'master_profile_id' => $masterModel->id,
            ]);
            if ($orders === null) {
                return [];
            }

            return $this->getFullArrayOrder($orders, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not all orders for master. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список заказов, привязанных к мастеру. Ошибка в БД.');
        }
    }

    /**
     * Список заказов, привязанных к данному салону (все заказы)
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getOrdersBySalon($userId, $userTypeName)
    {
        try {
            $salonModel = SalonProfileModel::model()->findByAttributes(['user_id' => $userId]);
            if ($salonModel === null) {
                return [];
            }
            $orders = OrderModel::model()->with('client')->with('salon')->findAllByAttributes([
                'salon_profile_id' => $salonModel->id,
            ]);
            if ($orders === null) {
                return [];
            }

            return $this->getFullArrayOrder($orders, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not all orders for salon. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список заказов, привязанных к салону. Ошибка в БД.');
        }
    }

    /**
     * Список заказов, привязанных к данному мастеру (назначенные заказы)
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getAgreeOrdersByMaster($userId, $userTypeName)
    {
        try {
            $masterModel = MasterProfileModel::model()->findByAttributes(['user_id' => $userId]);
            if ($masterModel === null) {
                return [];
            }
            $orders = OrderModel::model()->with('client')->with('master')->findAllByAttributes([
                'master_profile_id' => $masterModel->id,
                'status' => OrderStatusEnum::AGREE,
            ]);
            if ($orders === null) {
                return [];
            }

            return $this->getFullArrayOrder($orders, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get agree orders. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список согласованных заказов. Ошибка в БД.');
        }
    }


    /**
     * Список заказов, привязанных к данному салону (назначенные заказы)
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getAgreeOrdersBySalon($userId, $userTypeName)
    {
        try {
            $salonModel = SalonProfileModel::model()->findByAttributes(['user_id' => $userId]);
            if ($salonModel === null) {
                return [];
            }
            $orders = OrderModel::model()->with('client')->with('salon')->findAllByAttributes([
                'salon_profile_id' => $salonModel->id,
                'status' => OrderStatusEnum::AGREE,
            ]);
            if ($orders === null) {
                return [];
            }

            return $this->getFullArrayOrder($orders, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get agree orders. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список согласованных заказов. Ошибка в БД.');
        }
    }

    /**
     * Список заказов, привязанных к данному мастеру (выполненные заказы)
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getDoneOrdersByMaster($userId, $userTypeName)
    {
        try {
            $masterModel = MasterProfileModel::model()->findByAttributes(['user_id' => $userId]);
            if ($masterModel === null) {
                return [];
            }
            $orders = OrderModel::model()->with('client')->with('master')->findAllByAttributes([
                'master_profile_id' => $masterModel->id,
                'status' => OrderStatusEnum::DONE,
            ]);
            if ($orders === null) {
                return [];
            }

            return $this->getFullArrayOrder($orders, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get agree orders. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список согласованных заказов. Ошибка в БД.');
        }
    }

    /**
     * Список заказов, привязанных к данному мастеру (оцененные заказы)
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getAssessmentOrdersByMaster($userId, $userTypeName)
    {
        try {
            $masterModel = MasterProfileModel::model()->findByAttributes(['user_id' => $userId]);
            if ($masterModel === null) {
                return [];
            }
            $orders = OrderModel::model()->with('client')->with('master')->findAllByAttributes([
                'master_profile_id' => $masterModel->id,
                'status' => OrderStatusEnum::ASSESSMENT,
            ]);
            if ($orders === null) {
                return [];
            }

            return $this->getFullArrayOrder($orders, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get agree orders. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список согласованных заказов. Ошибка в БД.');
        }
    }


    /**
     * Список заказов, привязанных к данному салону (выполненные заказы)
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getDoneOrdersBySalon($userId, $userTypeName)
    {
        try {
            $salonModel = SalonProfileModel::model()->findByAttributes(['user_id' => $userId]);
            if ($salonModel === null) {
                return [];
            }
            $orders = OrderModel::model()->with('client')->with('salon')->findAllByAttributes([
                'salon_profile_id' => $salonModel->id,
                'status' => OrderStatusEnum::DONE,
            ]);
            if ($orders === null) {
                return [];
            }

            return $this->getFullArrayOrder($orders, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get agree orders. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список согласованных заказов. Ошибка в БД.');
        }
    }

    /**
     * Список заказов, привязанных к данному салону (оцененные заказы)
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getAssessmentOrdersBySalon($userId, $userTypeName)
    {
        try {
            $salonModel = SalonProfileModel::model()->findByAttributes(['user_id' => $userId]);
            if ($salonModel === null) {
                return [];
            }
            $orders = OrderModel::model()->with('client')->with('salon')->findAllByAttributes([
                'salon_profile_id' => $salonModel->id,
                'status' => OrderStatusEnum::ASSESSMENT,
            ]);
            if ($orders === null) {
                return [];
            }

            return $this->getFullArrayOrder($orders, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get agree orders. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список согласованных заказов. Ошибка в БД.');
        }
    }


    /**
     * Список заказов, предварительно привязанных к данному мастеру
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getNotAgreeOrdersByMaster($userId, $userTypeName)
    {
        try {
            $masterModel = MasterProfileModel::model()->findByAttributes(['user_id' => $userId]);
            if ($masterModel === null) {
                return [];
            }
            $orders = OrderModel::model()->findAllByAttributes([
                'master_profile_id' => null,
                'salon_profile_id' => null,
                'status' => OrderStatusEnum::SEND_TO_AGREE,
            ], [
                'condition' => '`t`.id IN (SELECT order_id 
                                           FROM buf_master_order 
                                           WHERE profile_id = :master_profile_id)',
                'params' => [':master_profile_id' => $masterModel->id],
                'order' => 'receipt_date ASC',
            ]);
            if ($orders === null) {
                return [];
            }

            return $this->getFullArrayOrder($orders, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get orders. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список заказов. Ошибка в БД.');
        }
    }

    /**
     * Список заказов, предварительно привязанных к данному салону
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getNotAgreeOrdersBySalon($userId, $userTypeName)
    {
        try {
            $salonModel = SalonProfileModel::model()->findByAttributes(['user_id' => $userId]);
            if ($salonModel === null) {
                return [];
            }
            $orders = OrderModel::model()->findAllByAttributes([
                'master_profile_id' => null,
                'salon_profile_id' => null,
                'status' => OrderStatusEnum::SEND_TO_AGREE,
            ], [
                'condition' => '`t`.id IN (SELECT order_id 
                                           FROM buf_salon_order 
                                           WHERE profile_id = :salon_profile_id)',
                'params' => [':salon_profile_id' => $salonModel->id],
                'order' => 'receipt_date ASC',
            ]);
            if ($orders === null) {
                return [];
            }

            return $this->getFullArrayOrder($orders, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get orders. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список заказов. Ошибка в БД.');
        }
    }

    /**
     * Создание заказа из предварительного. Затем предварительный удаляется.
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $userTypeId идентификатор типа пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function createFromBuf($userId, $userTypeId)
    {
        try {
            $transaction = $this->db->beginTransaction();
            (new OrderDsp)->createFromBuf($userId, $userTypeId, session_id());
            $newOrderId = Yii::app()->db->getLastInsertId();
            $deletedBufOrderCount = BufOrderModel::model()->deleteAll('owner_session_php_id = :session_id', [
                ':session_id' => session_id(),
            ]);
            //для последующего перехода на данный заказ
            if ($deletedBufOrderCount > 0) {
                Yii::app()->session['guestOrderId'] = $newOrderId;
            }
            $transaction->commit();

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not create order from buf. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось создать заказ из предварительного. Ошибка в БД.');
        }
    }


    /**
     * Список всех только созданных заказов по типу пользователя
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getCreatedOrders($userId, $userTypeName = null)
    {
        try {
            if ($userTypeName === null) {
                $orders = OrderModel::model()->with('client')->with('master')->with('salon')->with('service')
                    ->findAllByAttributes([
                        'status' => OrderStatusEnum::CREATED,
                    ], [
                        'condition' => 'owner_user_id <> :user_id',
                        'params' => [':user_id' => $userId],
                        'order' => '`t`.created_at DESC',
                    ]);
            } else {
                $userTypeId = UserTypeModel::model()->findByAttributes(['name' => $userTypeName])->id;
                $orders = OrderModel::model()->with('client')->with('master')->with('salon')->with('service')
                    ->findAllByAttributes([
                        'owner_user_type_id' => $userTypeId,
                        'status' => OrderStatusEnum::CREATED,
                    ], [
                        'condition' => 'owner_user_id <> :user_id',
                        'params' => [':user_id' => $userId],
                        'order' => '`t`.created_at DESC',
                    ]);
            }


            return $this->getFullArrayOrder($orders, $userId, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get orders. Fatal error.', [
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список созданных заказов. Ошибка в БД.');
        }
    }

    /**
     * Список всех только созданных заказов по типу пользователя близи данного пользователя
     *
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getCreatedOrdersNearBy($userId, $userType)
    {
        try {
            $userProfileModel = ProfileService::getInstance()->getProfileModel(null, $userType, $userId);
            $notifyModel = NotifyModel::model()->findByAttributes(['user_id' => $userId]);

            $sqlAcos = (new GeoHelper)->earthRadius . ' * ACOS(' .
                ' COS(:user_lat) * COS(`t`.latitude) * COS(:user_lng - `t`.longitude) + SIN(:user_lat) * SIN(`t`.latitude) ' .
                ')';
            $params = [];
            if (empty($notifyModel)) {
                $condition = $sqlAcos . ' <= ' . MASTER_RADIUS_FROM_CLIENT;
                $params = [
                    ':user_lat' => $userProfileModel->latitude,
                    ':user_lng' => $userProfileModel->longitude,
                ];
            } elseif ($notifyModel->type === NotifyTypeEnum::BY_RADIUS) {
                $condition = $sqlAcos . ' <= ' . IntVal($notifyModel->radius) * 1000;
                $params = [
                    ':user_lat' => $userProfileModel->latitude,
                    ':user_lng' => $userProfileModel->longitude,
                ];
            } elseif ($notifyModel->type === NotifyTypeEnum::BY_NEAR_SUBWAY) {
                $condition = '`t`.near_subway = :user_near_subway AND `t`.near_subway IS NOT NULL ';
                $params = [
                    ':user_near_subway' => $userProfileModel->near_subway,
                ];
            } else {
                throw new PublicMessageException('Неправильный тип оповещения.');
            }

            if ($userType === UserTypeEnum::MASTER) {
                $condition .= ' AND `t`.main_service_id IN (SELECT service_id FROM master_profile_service WHERE profile_id = :profile_id)';
                $condition .= ' AND (status = ' . OrderStatusEnum::CREATED .
                    ' OR (status = ' . OrderStatusEnum::SEND_TO_AGREE . ' AND `t`.id NOT IN (SELECT order_id 
                                           FROM buf_master_order 
                                           WHERE profile_id = :profile_id2) )) ';
                $params[':profile_id'] = $userProfileModel->id;
                $params[':profile_id2'] = $userProfileModel->id;
            } elseif ($userType === UserTypeEnum::SALON) {
                $condition .= ' AND `t`.main_service_id IN (SELECT service_id FROM salon_profile_service WHERE profile_id = :profile_id)';
                $condition .= ' AND (status = ' . OrderStatusEnum::CREATED .
                    ' OR (status = ' . OrderStatusEnum::SEND_TO_AGREE . ' AND `t`.id NOT IN (SELECT order_id 
                                           FROM buf_salon_order 
                                           WHERE profile_id = :profile_id2) )) ';
                $params[':profile_id'] = $userProfileModel->id;
                $params[':profile_id2'] = $userProfileModel->id;
            }

            $orderModels = OrderModel::model()->with('client')->with('service')->with('master')->with('salon')
                ->findAllByAttributes([], [
                    'condition' => $condition,
                    'params' => $params,
                    'order' => '`t`.created_at DESC',
                ]);

            return $this->getFullArrayOrder($orderModels, $userId, $userType);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get orders near by. Fatal error.', [
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список созданных ближайших заказов. Ошибка в БД.');
        }
    }


    /**
     * Список всех заказов по типу пользователя
     *
     * @param string $userTypeName тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getAllOrders($userTypeName = UserTypeEnum::CLIENT)
    {
        try {
            $orders = OrderModel::model()->with('ownerUser')->with('service')
                ->with('agreeMaster')->with('master')->with('agreeMaster.masterProfile')
                ->with('agreeSalon')->with('salon')->with('agreeSalon.salonProfile')
                ->findAllByAttributes([
                    'owner_user_type_id' => UserTypeModel::model()->findByAttributes([
                        'name' => $userTypeName,
                    ])->id,
                ]);

            return $this->getFullArrayOrder($orders, null, $userTypeName);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get orders. Fatal error.', [
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список всех заказов. Ошибка в БД.');
        }
    }

    /**
     * Список случайных 5 заказов
     *
     *
     * @return array
     * @throws PublicMessageException
     */
    public function rand5Orders()
    {
        try {
            $orders = OrderModel::model()->with('service')->findAllByAttributes([], [
                'condition' => 'plan_price > 0',
                'order' => 'rand()',
                'limit' => 5,
            ]);

            return $this->getFullArrayOrder($orders, null, null);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get orders. Fatal error.', [
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить случайных 5 заказов. Ошибка в БД.');
        }
    }

    /**
     * Заказ по Id
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     * @param integer $orderId номер заказа
     *
     * @return OrderModel
     * @throws PublicMessageException
     */
    public function getById($userId, $userType, $orderId)
    {
        try {
            if ($userType === UserTypeEnum::CLIENT) {
                $orderModel = OrderModel::model()->with('service')->with('master')->with('rusStatus')
                    ->findByAttributes([
                        'owner_user_id' => $userId,
                        'id' => $orderId,
                    ]);
                //для мастеров
            } elseif ($userType === UserTypeEnum::MASTER) {
                $masterModel = MasterProfileModel::model()->findByAttributes(['user_id' => $userId]);
                if ($masterModel === null) {
                    throw new PublicMessageException('Заказ не найден или не должен отображаться у данного типа пользователя.');
                }
                $orderModel = OrderModel::model()->with('service')->with('master')->with('clientOfsalon')->findByAttributes([
                    'id' => $orderId,
                ], [
                    'condition' => 'master_profile_id IS NULL OR salon_profile_id IS NULL OR master_profile_id = :master_profile_id',
                    'params' => [':master_profile_id' => $masterModel->id],
                ]);
                //для салонов
            } elseif ($userType === UserTypeEnum::SALON) {
                $salonModel = SalonProfileModel::model()->findByAttributes(['user_id' => $userId]);
                if ($salonModel === null) {
                    throw new PublicMessageException('Заказ не найден или не должен отображаться у данного типа пользователя.');
                }
                $orderModel = OrderModel::model()->with('service')->with('salon')->findByAttributes([
                    'id' => $orderId,
                ], [
                    'condition' => 'master_profile_id IS NULL OR salon_profile_id IS NULL OR salon_profile_id = :salon_profile_id',
                    'params' => [':salon_profile_id' => $salonModel->id],
                ]);
            }

            if ($orderModel === null) {
                throw new PublicMessageException('Заказ не найден или не должен отображаться у данного типа пользователя.');
            }

            //из-за Twig - он почему-то не понимает null
            $orderModel->plan_price = ($orderModel->plan_price === null ? '' : $orderModel->plan_price);
            $orderModel->salon_profile_id = ($orderModel->salon_profile_id === null ? '' : $orderModel->salon_profile_id);
            $orderModel->master_profile_id = ($orderModel->master_profile_id === null ? '' : $orderModel->master_profile_id);
            $orderModel->description = ($orderModel->description === null ? '' : $orderModel->description);
            $orderModel->receipt_date = $this->dateFormatter->formatDateTime($orderModel->receipt_date);

            return $orderModel;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get orders. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список заказов. Ошибка в БД.');
        }
    }


    /**
     * Список мастеров по заказу, которым отправлено предложение
     *
     * @param ClientProfileModel|SalonProfileModel $clientModel данные профиля пользователя (это либо клиент, либо салон при заказе клиента через оператора салона)
     * @param OrderModel $orderModel данные заказа
     *
     * @return array
     * @throws PublicMessageException
     */
    public function findBufMasterByOrder($clientModel, OrderModel $orderModel)
    {
        try {
            if ($orderModel->owner_user_id !== $clientModel->user_id) {
                throw new PublicMessageException('Заказ не принадлежит данному пользователю.');
            }

            return SearchService::getInstance()->getFullArrayMastersToOrder(
                (new MasterProfileDsp)->findNotAgreeMaster($orderModel->id),
                $clientModel->address_coord
            );
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get not agree masters. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список мастеров, которым отправлено предложение. Ошибка в БД.');
        }
    }

    /**
     * Список салонов по заказу, которым отправлено предложение
     *
     * @param ClientProfileModel $clientModel данные профиля пользователя
     * @param OrderModel $orderModel данные заказа
     *
     * @return array
     * @throws PublicMessageException
     */
    public function findBufSalonByOrder(ClientProfileModel $clientModel, OrderModel $orderModel)
    {
        try {
            if ($orderModel->owner_user_id !== $clientModel->user_id) {
                throw new PublicMessageException('Заказ не принадлежит данному пользователю.');
            }

            return SearchService::getInstance()->getFullArraySalonsToOrder(
                (new SalonProfileDsp)->findNotAgreeSalon($orderModel->id),
                $clientModel->address_coord
            );
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get not agree salons. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список салонов, которым отправлено предложение. Ошибка в БД.');
        }
    }


    /**
     * Список салонов по заказу, которые сами отправили предложение
     *
     * @param ClientProfileModel $clientModel данные профиля пользователя
     * @param OrderModel $orderModel данные заказа
     *
     * @return array
     * @throws PublicMessageException
     */
    public function findBufOwnSalonByOrder(ClientProfileModel $clientModel, OrderModel $orderModel)
    {
        try {
            if ($orderModel->owner_user_id !== $clientModel->user_id) {
                throw new PublicMessageException('Заказ не принадлежит данному пользователю.');
            }

            return SearchService::getInstance()->getFullArraySalonsToOrder(
                (new SalonProfileDsp)->findNotAgreeOwnSalon($orderModel->id),
                $clientModel->address_coord
            );
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get not argee own salons. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список салонов, которые сами отправили предложение. Ошибка в БД.');
        }
    }

    /**
     * Список мастеров по заказу, которые сами отправили предложение
     *
     * @param @param ClientProfileModel|SalonProfileModel $clientOrSalonModel  данные профиля пользователя (это либо клиент, либо салон при заказе клиента через оператора салона)
     * @param OrderModel $orderModel данные заказа
     *
     * @return array
     * @throws PublicMessageException
     */
    public function findBufOwnMasterByOrder($clientOrSalonModel, OrderModel $orderModel)
    {
        try {
            if ($orderModel->owner_user_id !== $clientOrSalonModel->user_id) {
                throw new PublicMessageException('Заказ не принадлежит данному пользователю.');
            }

            return SearchService::getInstance()->getFullArrayMastersToOrder(
                (new MasterProfileDsp)->findNotAgreeOwnMaster($orderModel->id),
                $clientOrSalonModel->address_coord
            );
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get not agree own masters. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список мастеров, которые сами отправили предложение. Ошибка в БД.');
        }
    }


    /**
     * Выдает цвет плашки заказа (в виде CSS класса)
     *
     * @param OrderModel $orderModel
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     *
     * @return string
     * @throws Exception
     */
    public function getOrderColor(OrderModel $orderModel, $userId, $userType)
    {
        $orderColor = 'btn-pink';

        if (IntVal($orderModel->status) === OrderStatusEnum::SEND_TO_AGREE) {
            if ($userType === UserTypeEnum::MASTER) {
                $bufAgreeModel = BufMasterOrderModel::model()->findByPk([
                    'profile_id' => ProfileService::getInstance()->getProfileModel(
                        null,
                        $userType,
                        $userId
                    )->id,
                    'order_id' => $orderModel->id,
                ]);
                if ($bufAgreeModel !== null && $bufAgreeModel->master_agree === true) {
                    $orderColor = 'btn-primary';
                }
            } elseif ($userType === UserTypeEnum::SALON) {
                $bufAgreeModel = BufSalonOrderModel::model()->findByPk([
                    'profile_id' => ProfileService::getInstance()->getProfileModel(
                        null,
                        $userType,
                        $userId
                    )->id,
                    'order_id' => $orderModel->id,
                ]);
                if ($bufAgreeModel !== null && $bufAgreeModel->salon_agree === true) {
                    $orderColor = 'btn-primary';
                }
            }

        } else {
            if (IntVal($orderModel->status) === OrderStatusEnum::AGREE) {
                $orderColor = 'btn-warning';
            } else {
                if (IntVal($orderModel->status) === OrderStatusEnum::DONE
                    || IntVal($orderModel->status) === OrderStatusEnum::ASSESSMENT) {
                    $orderColor = 'btn-success';
                }
            }
        }

        return $orderColor;
    }

    /**
     * Перевод просроченных заказов в статус 'Просроченные'
     *
     */
    public function outDateOrders()
    {
        try {
            $outdatedCount = OrderModel::model()->updateAll([
                'status' => OrderStatusEnum::OUT_DATE
            ], [
                'condition' => 'receipt_date IS NOT NULL AND receipt_date < DATE_SUB(NOW(), INTERVAL 1 DAY)',
            ]);

            echo $outdatedCount . ' orders outdated';
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not outdate orders. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);
        }
    }

    /**
     * Выдает массив отзывов для отображения
     *
     * @param OrderModel[] $orderModels
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     *
     * @return array
     * @throws Exception
     */
    public function getFullArrayOrder(array $orderModels, $userId, $userType)
    {
        $userProfileModel = ProfileService::getInstance()->getProfileModel(null, $userType, $userId);

        $orders = [];
        foreach ($orderModels as $orderModel) {
            $order = $orderModel->getAttributes();
            $order['ownerUser'] = isset($orderModel->ownerUser) ? $orderModel->ownerUser->getAttributes() : null;

            if (!empty($orderModel->agreeMaster)) {
                $agreeMasterProfileIds = [];
                foreach ($orderModel->agreeMaster as $agreeMasterData) {
                    $agreeMasterProfileIds[] = $agreeMasterData->masterProfile->last_name .
                        ' (' . $agreeMasterData->masterProfile->phone . ')';
                }
                $order['agreeMasterIds'] = join(', ', $agreeMasterProfileIds);
            }
            if (!empty($orderModel->agreeSalon)) {
                $agreeSalonProfileIds = [];
                foreach ($orderModel->agreeSalon as $agreeSalonData) {
                    $agreeSalonProfileIds[] = $agreeSalonData->salonProfile->name .
                        ' (' . $agreeSalonData->salonProfile->phone . ')';
                }
                $order['agreeSalonIds'] = join(', ', $agreeSalonProfileIds);
            }

            $order['service'] = $orderModel->service->getAttributes();
            $order['status'] = IntVal($orderModel->status);
            $order['receipt_date'] = $this->dateFormatter->format($orderModel->receipt_date);
            $order['receipt_datetime'] = $this->dateFormatter->formatDateTime($orderModel->receipt_date);

            if (!empty($orderModel->address_coord) && !empty($userProfileModel->address_coord)) {
                $order['distance'] = SearchService::getInstance()->getDistance(
                    $orderModel->address_coord,
                    $userProfileModel->address_coord
                );
            } else {
                $order['distance'] = null;
            }

            $order['color'] = $this->getOrderColor($orderModel, $userId, $userType);
            $order['orderPhotoUrl'] = !empty($orderModel->photo_file_name) ? IMG_ORDER_DIR . $orderModel->photo_file_name : '';
            $orders[] = $order;
        }

        return $orders;
    }
}
