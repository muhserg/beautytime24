<?php

/**
 * Обеспечивает работу с сообщениями между пользователями
 */
class MessageService
{
    private static $messageService = null;

    /** var DateFormatter */
    private $dateFormatter;

    /** var ShortFormatter */
    private $shortFormatter;

    /** var CDbConnection */
    private $db;

    /**
     * @return MessageService
     */
    public static function getInstance()
    {
        if (self::$messageService === null) {
            self::$messageService = new self();
        }
        return self::$messageService;
    }

    public function __construct()
    {
        $this->dateFormatter = new DateFormatter();
        $this->shortFormatter = new ShortFormatter();
        $this->db = Yii::app()->db;
    }


    /**
     * Получение списка пользователей, с которыми был контакт у данного пользователя
     *
     * @param integer $userId идентификатор пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getUsersByUser($userId)
    {
        try {
            $users = (new MessageDsp)->getUsersByUser($userId);
            if (empty($users)) {
                return [];
            }

            return $this->getFullArrayUser($users);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get messages of user. Fatal error.', [
                'userId' => $userId,
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить сообщения пользователя. Ошибка в БД.');
        }
    }

    /**
     * Получение сообщений между конретными 2мя пользователями (туда и обратно)
     *
     * @param integer $fromUserId идентификатор пользователя - отправителя
     * @param integer $toUserId идентификатор пользователя - получателя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getByFromToUser($fromUserId, $toUserId)
    {
        try {
            $messageModels = MessageModel::model()
                ->with('toUsers.fromProfileClientData')
                ->with('toUsers.fromProfileMasterData')
                ->with('toUsers.fromProfileSalonData')
                ->with('toUsers.toProfileClientData')
                ->with('toUsers.toProfileMasterData')
                ->with('toUsers.toProfileSalonData')
                ->findAll([
                    'condition' => '(`t`.from_user_id = :from_user_id AND toUsers.to_user_id = :to_user_id) '.
                                   'OR (`t`.from_user_id = :to_user_id2 AND toUsers.to_user_id = :from_user_id2) '.
                                   ' AND is_moderated = true',
                    'params' => [
                        ':from_user_id' => $fromUserId,
                        ':to_user_id' => $toUserId,
                        ':from_user_id2' => $fromUserId,
                        ':to_user_id2' => $toUserId,
                    ],
                    'order' => '`t`.created_at ASC'
                ]);
            if (empty($messageModels)) {
                return [];
            }

            return $this->getFullArrayMessage($messageModels);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get messages of user. Fatal error.', [
                'fromUserId' => $fromUserId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить сообщения пользователя. Ошибка в БД.');
        }
    }


    /**
     * Получение сообщений от всех пользователей, привязанных к конкретному пользователю (пользователь - отправитель)
     *
     * @param integer $fromUserId идентификатор пользователя - отправителя
     * @param integer $toUserId идентификатор пользователя - получателя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getByFromUser($fromUserId)
    {
        try {
            $messageModels = MessageModel::model()
                ->with('toUsers.fromProfileClientData')
                ->with('toUsers.fromProfileMasterData')
                ->with('toUsers.fromProfileSalonData')
                ->with('toUsers.toProfileClientData')
                ->with('toUsers.toProfileMasterData')
                ->with('toUsers.toProfileSalonData')
                ->findAll([
                    'condition' => '`t`.from_user_id = :from_user_id',
                    'params' => [
                        ':from_user_id' => $fromUserId,
                    ],
                ]);
            if (empty($messageModels)) {
                return [];
            }

            return $this->getFullArrayMessage($messageModels);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get messages of user. Fatal error.', [
                'fromUserId' => $fromUserId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить сообщения пользователя. Ошибка в БД.');
        }
    }


    /**
     * Получение сообщений от всех пользователей, привязанных к конкретному пользователю (пользователь - получатель)
     *
     * @param integer $toUserId идентификатор пользователя - получателя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getByToUser($toUserId)
    {
        try {
            $messageUserModels = UsersMessageModel::model()->with('message')
                ->with('fromProfileClientData')
                ->with('fromProfileMasterData')
                ->with('fromProfileSalonData')
                ->with('toProfileClientData')
                ->with('toProfileMasterData')
                ->with('toProfileSalonData')
                ->findAllByAttributes([
                    'to_user_id' => $toUserId,
                ]);

            if (empty($messageUserModels)) {
                return [];
            }

            return $this->getFullArrayUserMessage($messageUserModels);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get messages of user. Fatal error.', [
                'toUserId' => $toUserId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить сообщения пользователя. Ошибка в БД.');
        }
    }


    /**
     * Получение сообщения пользователя по id
     *
     * @param integer $id идентификатор сообщения
     * @param integer $fromUserId идентификатор пользователя - отправителя
     *
     * @return MessageModel
     * @throws PublicMessageException
     */
    public function getById($id, $fromUserId)
    {
        try {
            $messageModel = MessageModel::model()->with('toUsers')->findByAttributes([
                'id' => $id,
                'user_id' => $fromUserId,
            ]);
            if (empty($messageModel)) {
                throw new PublicMessageException('Не удалось найти сообщение для данного пользователя.');
            }

            return $messageModel;
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get message. Fatal error.', [
                'fromUserId' => $fromUserId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось найти сообщение для данного пользователя. Ошибка в БД.');
        }
    }


    /**
     * Создание сообщения пользователя и возрат его для отображения
     *
     * @param integer $fromUserId идентификатор пользователя-отправителя
     * @param integer $fromUserType тип пользователя-отправителя
     * @param array $toUserIds идентификаторы пользователей-получателей
     * @param string $message текст сообщения
     * @param integer $orderId идентификатор заказа (когда создается коммента к заказу)
     * @param string $title заголовок сообщения
     * @param string $file путь к файлу, привязанному к сообщению
     *
     * @return array
     * @throws PublicMessageException
     */
    public function create($fromUserId, $fromUserType, array $toUserIds, $message, $orderId = null, $title = null, $file = null)
    {
        if (empty($fromUserId) || empty($toUserIds) || empty($message)) {
            throw new PublicMessageException('Отсутствуют входные параметры.');
        }

        try {
            //получаем модель
            $messageModel = new MessageModel;

            //заполняем модель
            $messageModel->from_user_id = $fromUserId;
            $messageModel->message = $message;
            $messageModel->title = $title;
            $messageModel->file = $file;
            $messageModel->is_moderated = !IS_MODERATE_MESSAGES;

            if($orderId !== null){
                $messageModel->order_id = $orderId;
            }

            $isCommitInThisFunc = false;
            if ($this->db->getCurrentTransaction() === null) {
                $isCommitInThisFunc = true;
                $transaction = $this->db->beginTransaction();
            }

            if (!$messageModel->save()) {
                BtLogger::getLogger()->error('Can not save message.', [
                    'fromUserId' => $fromUserId,
                    'toUserIds' => $toUserIds,
                    'message' => $message,
                    'error' => $messageModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось сохранить сообщение.');
            }

            foreach ($toUserIds as $toUserId) {
                $usersMessageModel = new UsersMessageModel;
                $usersMessageModel->message_id = $messageModel->id;
                $usersMessageModel->from_user_id = $fromUserId;
                $usersMessageModel->to_user_id = $toUserId;
                $usersMessageModel->to_user_type_name = UserModel::model()->with('userType')->findByPk(
                    $toUserId
                )->userType->name;

                if (!$usersMessageModel->save()) {
                    BtLogger::getLogger()->error('Can not save to user in message.', [
                        'fromUserId' => $fromUserId,
                        'toUserId' => $toUserId,
                        'error' => $usersMessageModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Не удалось сохранить получателя в сообщении.');
                }
            }

            if ($isCommitInThisFunc === true) {
                $transaction->commit();
            }

            $userProfileModel = ProfileService::getInstance()->getProfileModel(null, $fromUserType, $fromUserId);
            $fromFio = '';
            if ($fromUserType === UserTypeEnum::CLIENT) {
                $fromFio = $userProfileModel->last_name . ' ' . $userProfileModel->first_name;
            } elseif ($fromUserType === UserTypeEnum::MASTER) {
                $fromFio = $userProfileModel->last_name . ' ' . $userProfileModel->first_name;
            } elseif ($fromUserType === UserTypeEnum::SALON) {
                $fromFio = $userProfileModel->name;
            }

            return array_merge([
                'date' => $this->dateFormatter->formatDateTime('now'),
                'fromFio' => $fromFio,
            ], $messageModel->getAttributes());
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save message. Fatal error.', [
                'fromUserId' => $fromUserId,
                'toUserIds' => $toUserIds,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось сохранить сообщение. Ошибка в БД.');
        }
    }


    /**
     * Выдает массив параметров сообщения для отображения
     *
     * @param MessageModel[] $messageModels
     *
     * @return array
     * @throws Exception
     */
    public function getFullArrayMessage(array $messageModels)
    {
        $messages = [];
        foreach ($messageModels as $messageModel) {
            $toFio = null;
            if (isset($messageModel->toUsers) && is_object($messageModel->toUsers->toProfileClientData) === true) {
                $profileData = $messageModel->toUsers->toProfileClientData;
                $toFio = $profileData->last_name . ' ' . $profileData->first_name;
            }
            if (isset($messageModel->toUsers) && is_object($messageModel->toUsers->toProfileMasterData) === true) {
                $profileData = $messageModel->toUsers->toProfileMasterData;
                $toFio = $profileData->last_name . ' ' . $profileData->first_name;
            }
            if (isset($messageModel->toUsers) && is_object($messageModel->toUsers->toProfileSalonData) === true) {
                $toFio = $messageModel->toUsers->toProfileSalonData->name;
            }

            $fromFio = null;
            if (is_object($messageModel->fromProfileClientData) === true) {
                $profileData = $messageModel->fromProfileClientData;
                $fromFio = $profileData->last_name . ' ' . $profileData->first_name;
            }
            if (is_object($messageModel->fromProfileMasterData) === true) {
                $profileData = $messageModel->fromProfileMasterData;
                $fromFio = $profileData->last_name . ' ' . $profileData->first_name;
            }
            if (is_object($messageModel->fromProfileSalonData) === true) {
                $fromFio = $messageModel->fromProfileSalonData->name;
            }

            $messages[] = array_merge([
                'date' => $this->dateFormatter->formatDateTime($messageModel->created_at),
                'toFio' => $toFio,
                'fromFio' => $fromFio,
                'user_id' => $messageModel->toUsers->to_user_id,
            ], $messageModel->getAttributes());
        }

        return $messages;
    }


    /**
     * Выдает массив параметров сообщения для отображения
     *
     * @param MessageModel[] $messageUserModels
     *
     * @return array
     * @throws Exception
     */
    public function getFullArrayUserMessage(array $messageUserModels)
    {
        $messages = [];
        foreach ($messageUserModels as $messageUserModel) {
            $toFio = null;
            if (isset($messageUserModel->toProfileClientData) && is_object($messageUserModel->toProfileClientData) === true) {
                $profileData = $messageUserModel->toProfileClientData;
                $toFio = $profileData->last_name . ' ' . $profileData->first_name;
            }
            if (isset($messageUserModel->toProfileMasterData) && is_object($messageUserModel->toProfileMasterData) === true) {
                $profileData = $messageUserModel->toProfileMasterData;
                $toFio = $profileData->last_name . ' ' . $profileData->first_name;
            }
            if (isset($messageUserModel->toProfileSalonData) && is_object($messageUserModel->toProfileSalonData) === true) {
                $toFio = $messageUserModel->toProfileSalonData->name;
            }

            $fromFio = null;
            if (is_object($messageUserModel->fromProfileClientData) === true) {
                $profileData = $messageUserModel->fromProfileClientData;
                $fromFio = $profileData->last_name . ' ' . $profileData->first_name;
            }
            if (is_object($messageUserModel->fromProfileMasterData) === true) {
                $profileData = $messageUserModel->fromProfileMasterData;
                $fromFio = $profileData->last_name . ' ' . $profileData->first_name;
            }
            if (is_object($messageUserModel->fromProfileSalonData) === true) {
                $fromFio = $messageUserModel->fromProfileSalonData->name;
            }

            $messages[] = array_merge([
                'date' => $this->dateFormatter->formatDateTime($messageUserModel->message->created_at),
                'toFio' => $toFio,
                'fromFio' => $fromFio,
                'user_id' => $messageUserModel->to_user_id,
            ], $messageUserModel->message->getAttributes());

        }

        return $messages;
    }


    /**
     * Выдает массив отправителей и получателей
     *
     * @param array $users
     *
     * @return array
     * @throws Exception
     */
    public function getFullArrayUser(array $users)
    {
        foreach ($users as $num => $user) {

            $users[$num]['avatarUrl'] = IMG_PATH_NO_AVATAR;
            if (!empty($user['cp_fio'])) {
                $users[$num]['fio'] = $user['cp_fio'];
                $users[$num]['avatarUrl'] = !empty($user['cp_avatar']) ? IMG_SMALL_DIR . $user['cp_avatar'] : IMG_PATH_NO_AVATAR;
            } elseif (!empty($user['mp_fio'])) {
                $users[$num]['fio'] = $user['mp_fio'];
                $users[$num]['avatarUrl'] = !empty($user['mp_avatar']) ? IMG_SMALL_DIR . $user['mp_avatar'] : IMG_PATH_NO_AVATAR;
            } elseif (!empty($user['sp_name'])) {
                $users[$num]['fio'] = $user['sp_name'];
                $users[$num]['avatarUrl'] = !empty($user['sp_avatar']) ? IMG_DIR . $user['sp_avatar'] : IMG_PATH_NO_AVATAR;
            }

            $users[$num]['img_class'] = ($users[$num]['avatarUrl'] !== IMG_PATH_NO_AVATAR ? 'round_photo' : 'round_no_photo');
            $users[$num]['last_message_date'] = $this->dateFormatter->formatDateTime($user['last_message_date']);
            $users[$num]['short_last_message'] = $this->shortFormatter->format($user['last_message']);
        }

        return $users;
    }
}
