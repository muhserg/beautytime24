<?php

class FeedBackService
{
    private static $feedbackService = null;

    /**
     * @return FeedBackService
     */
    public static function getInstance()
    {
        if (self::$feedbackService === null) {
            self::$feedbackService = new self();
        }
        return self::$feedbackService;
    }

    /**
     * Отправка сообщения обратной связи
     *
     * @param string $senderName имя отправителя (у авторизованного пользователя - по умолчанию это логин)
     * @param string $phone номер телефона
     * @param string $email почта пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function sendFeedBack($senderName, $phone, $email, $comment)
    {
        if (empty($senderName) || (empty($phone) && empty($email)) || empty($comment)) {
            throw new PublicMessageException('Отсутствуют некоторые входные параметры.');
        }
        $userId = isset(Yii::app()->session['user']['id']) ? Yii::app()->session['user']['id'] : null;

        try {
            $feedbackModel = new FeedBackModel;
            $feedbackModel->sender_name = $senderName;
            $feedbackModel->phone = (new PhoneHelper)->formatPhoneForSave($phone);
            $feedbackModel->email = $email;
            $feedbackModel->user_id = $userId;
            $feedbackModel->comment = $comment;

            if (!$feedbackModel->save()) {
                BtLogger::getLogger()->error('Can not send feedback.', [
                    'sender_name' => $senderName,
                    'phone' => $phone,
                    'email' => $email,
                    'userId' => $userId,
                    'comment' => $comment,
                    'error' => $feedbackModel->getErrors(),
                ]);
                throw new PublicMessageException('Не удалось отправить сообщение.');
            }

            //дублируем на электронную почту
            SwiftMailer::sendEmailMessage(
                [ADMIN_EMAIL1, ADMIN_EMAIL2],
                'Обратная связь от ' . SITE_HOST,
                'Обратная связь от ' . $feedbackModel->phone . ', Сообщение: ' . $feedbackModel->comment
            );
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not send feedback. Fatal error.', [
                'sender_name' => $senderName,
                'phone' => $phone,
                'email' => $email,
                'userId' => $userId,
                'comment' => $comment,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось отправить сообщение. Ошибка в БД.');
        }

        return true;
    }
}
