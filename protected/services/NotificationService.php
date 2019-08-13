<?php

/**
 * Сервис для работы с уведомлениями
 */
class NotificationService
{
    private static $notificationService = null;

    /**
     * @return NotificationService
     */
    public static function getInstance()
    {
        if (self::$notificationService === null) {
            self::$notificationService = new self();
        }
        return self::$notificationService;
    }


    /**
     * Выдача настроек уведомлений
     *
     * @param integer $userId идентификатор пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getSettings($userId)
    {
        if (empty($userId)) {
            throw new PublicMessageException('Пользователь не авторизован.');
        }

        try {
            //получаем модель
            $notifyModel = NotifyModel::model()->findByAttributes(['user_id' => $userId]);
            if ($notifyModel === null) {
                return [];
            }

            return $notifyModel->getAttributes();
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save notify settings. Fatal error.', [
                'user_id' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось сохранить настройки уведомлений. Ошибка в БД.');
        }
    }

    /**
     * Сохранение настроек уведомлений
     *
     * @param integer $userId идентификатор пользователя
     * @param string $notifyType тип оповещения
     * @param float $radius радиус расстояния до клиента (в км)
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function saveSettings($userId, $notifyType, $radius)
    {
        if (empty($userId)) {
            throw new PublicMessageException('Пользователь не авторизован.');
        }

        try {
            //получаем модель
            $notifyModel = NotifyModel::model()->findByAttributes(['user_id' => $userId]);
            if ($notifyModel === null) {
                $notifyModel = new NotifyModel;
            }

            //заполняем модель
            $notifyModel->user_id = $userId;
            $notifyModel->type = $notifyType;
            $notifyModel->radius = $radius;

            if (!$notifyModel->save()) {
                BtLogger::getLogger()->error('Can not save notify settings.', [
                    'user_id' => $userId,
                    'error' => $notifyModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось сохранить настройки уведомлений.');
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save notify settings. Fatal error.', [
                'user_id' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось сохранить настройки уведомлений. Ошибка в БД.');
        }
    }

}
