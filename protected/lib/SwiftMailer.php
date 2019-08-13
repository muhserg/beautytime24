<?php

/**
 * Сервис для отправки почты
 */
class SwiftMailer extends \Swift_Mailer
{
    /**
     * @param string $subject
     * @param null $body
     * @param string $contentType
     * @param string $charset
     * @return Swift_Message
     */
    public function createMessage(
        $subject = MAIL_SUBJECT_BT,
        $body = null,
        $contentType = 'text/html',
        $charset = 'utf-8'
    )
    {
        return (new Swift_Message($subject, $body, $contentType, $charset))
            ->setFrom(MAIL_FROM_EMAIL_DEFAULT, MAIL_FROM_NAME_DEFAULT);
    }

    public static function create()
    {
        $transport = new \Swift_SmtpTransport(SMTP_SERVER_ADDR, SMTP_SERVER_PORT);
        $transport->setUsername(MAIL_LOGIN);
        $transport->setPassword(MAIL_PASSWORD);
        if (SMTP_ENCRYPTION !== '') {
            $transport
                ->setEncryption(SMTP_ENCRYPTION)
                ->setStreamOptions(
                    [
                        'ssl' => [
                            'allow_self_signed' => true,
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ],
                    ]
                );
        }
        return new self($transport);
    }

    /**
     * отправка email
     *
     * @param array $to список адресов email, на которые можно отправить сообщения
     * @param string $subject тема сообщения
     * @param string $body сообщение, которое нужно отправить
     *
     * @return bool
     * @throws PublicMessageException
     */
    public static function sendEmailMessage($arrayTo, $subject, $body)
    {
        $failures = [];

        //для хостинга
        if (SMTP_SERVER_ADDR === '') {
            foreach ($arrayTo as $to) {
                if (mail($to, $subject, $body) === false) {
                    $failures[] = $to;
                }
            }
            if (!empty($failures)) {
                throw new PublicMessageException('Не удалось отправить email.');
            }

            return true;
        }

        $shiftMailer = SwiftMailer::create();
        $message = $shiftMailer->createMessage($subject, $body);
        $message->setTo($arrayTo);
        try {
            $shiftMailer->send($message, $failures);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Send mail error.', [$e]);

            throw new PublicMessageException('Не удалось отправить email.');
        }

        return true;
    }
}
