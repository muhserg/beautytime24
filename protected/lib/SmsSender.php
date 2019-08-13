<?php

/**
 * Сервис для отправки СМС
 */
class SmsSender
{
    const STATUS_CODE_SUCCESS = 100;
    const STATUS_OK = 'OK';

    /**
     * Отправка СМС провайдеру через http
     *
     * @param string $url URL куда отправить
     * @param string $postData данные POST
     *
     * @return string
     * @throws PublicMessageException
     */
    public function send($url, $postData = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);

        if (isset($postData)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        }

        // Pass TRUE or 1 if you want to wait for and catch the response against the request made
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // For Debug mode; shows up any error encountered during the operation
        curl_setopt($curl, CURLOPT_VERBOSE, 1);

        $response = curl_exec($curl);
        if ($response === false || $response === '') {
            BtLogger::getLogger()->error('Send SMS error.',
                [
                    'url' => $url,
                    'post_data' => $postData,
                ]
            );
            throw new PublicMessageException('Не удалось отправить СМС.');
        }

        return $response;
    }

    /**
     * Отправка СМС
     *
     * @param array $phones список телефонов, на которые можно отправить сообщения
     * @param string $message сообщение, которое нужно отправить
     * @param string $messageType тип сообщения
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function sendMessage($phones, $message, $messageType = 'unknown')
    {
        if (SEND_SMS_PROVIDER === 'SMS.RU') {
            $response = $this->sendViaSmsRu($phones, $message);

            try {
                foreach ($phones as $phone) {
                    $smsModel = new SmsModel();
                    $smsModel->phone = $phone;
                    $smsModel->type = $messageType;
                    $smsModel->message = $message;
                    $smsModel->response = json_encode($response);
                }

                if (!$smsModel->save()) {
                    BtLogger::getLogger()->error('Can not save sms send result.', [
                        'phone' => $phone,
                        'message' => $message,
                        'response' => $response,
                        'error' => $smsModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Ошибка сохранения результата отправки СМС.');
                }
            } catch (Exception $e) {
                BtLogger::getLogger()->error('Can not save sms send result. DB error.', [
                    'phone' => $phone,
                    'message' => $message,
                    'response' => $response,
                    'error' => $smsModel->getErrors(),
                ]);

                throw new PublicMessageException('Ошибка сохранения результата отправки СМС. Ошибка в БД.');
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Отправка СМС через сайт sms.ru
     *
     * @param array $phones список телефонов, на которые можно отправить сообщения
     * @param string $message сообщение, которое нужно отправить
     *
     * @return array
     * @throws PublicMessageException
     */
    public function sendViaSmsRu($phones, $message)
    {
        if (!is_array($phones) || count($phones) <= 0 || !isset($message) || $message === '') {
            throw new PublicMessageException('Отсутствует номер телефона или текст сообщения.');
        }

        $url = 'https://sms.ru/sms/send?api_id=' . SMS_RU_APP_ID .
            '&to=' . join(',', $phones) . '&msg=' . urlencode($message) . '&json=1&from=' . SMS_SENDER_NAME;
        $response = json_decode($this->send($url), true);
        if (empty($response)) {
            BtLogger::getLogger()->error('Send SMS error. Empty Response.',
                [
                    'url' => $url,
                    'phones' => $phones,
                    'message' => $message,
                ]
            );
            throw new PublicMessageException('Не удалось отправить СМС. Пустой ответ.');
        }
        if ($response['status_code'] !== self::STATUS_CODE_SUCCESS || empty($response['sms'])) {
            BtLogger::getLogger()->error('Send SMS error. Provider error.', [
                [
                    'url' => $url,
                    'phones' => $phones,
                    'message' => $message,
                    'status_code' => $response['status_code'],
                ],
            ]);
            throw new PublicMessageException('Не удалось отправить СМС. Ошибка провайдера.');
        }

        foreach ($response['sms'] as $phone => $smsResponseInfo) {
            if ($smsResponseInfo['status'] !== self::STATUS_OK) {
                BtLogger::getLogger()->error('Send SMS error. Provider error.', [
                    [
                        'url' => $url,
                        'phone' => $phone,
                        'error_code' => $smsResponseInfo['status_code'],
                        'error_text' => $smsResponseInfo['status_text'],
                    ],
                ]);

                throw new PublicMessageException('Не удалось отправить СМС. Ошибка провайдера.');
            }
        }

        return $response;
    }

}
