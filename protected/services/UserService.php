<?php

/**
 * To transparently support this function on older versions of PHP
 */
if (!function_exists('hash_equals')) {
    function hash_equals($str1, $str2)
    {
        if (strlen($str1) != strlen($str2)) {
            return false;
        } else {
            $res = $str1 ^ $str2;
            $ret = 0;
            for ($i = strlen($res) - 1; $i >= 0; $i--) {
                $ret |= ord($res[$i]);
            }
            return !$ret;
        }
    }
}

/**
 * Сервис для работы с авторизацией
 */
class UserService
{
    private static $userService = null;

    /**
     * @return UserService
     */
    public static function getInstance()
    {
        if (self::$userService === null) {
            self::$userService = new self();
        }
        return self::$userService;
    }

    /**
     * Регистрация нового пользователя
     *
     * @param string $login логин пользователя
     * @param string $pass пароль пользователя
     * @param string $userType тип пользователя
     * @param string $phone номер телефона
     * @param string $email почта пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function register($login, $pass, $userType, $phone, $email)
    {
        if (empty($login) || empty($pass) || empty($userType) || empty($phone) || empty($email)) {
            throw new PublicMessageException('Отсутствуют некоторые входные параметры.');
        }

        $userTypeNumeric = UserTypeModel::model()->findByAttributes(['name' => $userType]);
        if (empty($userTypeNumeric)) {
            throw new PublicMessageException('Такого типа пользователя не существует.');
        }

        try {
            $userModel = new UserModel;
            $userModel->login = $login;
            $userModel->pass = $this->getHashPass($pass);
            $userModel->type = $userTypeNumeric['id'];
            $userModel->phone = (new PhoneHelper)->formatPhoneForSave($phone);
            $userModel->email = $email;
            $userModel->email_code = md5(rand());
            $userModel->phone_code = $this->generatePhoneCodeConfirm();

            if (!$userModel->save()) {
                BtLogger::getLogger()->error('Can not register user.', [
                    'login' => $login,
                    'phone' => $phone,
                    'email' => $email,
                    'error' => $userModel->getErrors(),
                ]);
                throw new PublicMessageException('Не удалось зарегистрировать пользователя.');
            }

            $sessionId = session_id();
            if (empty($sessionId)) {
                session_start();
                StatisticService::getInstance()->createSession();
            }
            Yii::app()->session['user'] = [
                'id' => $userModel->id,
                'type' => $userType,
                'login' => $userModel->login,
                'phone' => $userModel->phone,
                'phone_code' => $userModel->phone_code,
                'pass' => $userModel->pass,
            ];

            OrderService::getInstance()->createFromBuf($userModel->id, $userModel->type);
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not register user. Fatal error.', [
                'login' => $login,
                'phone' => $phone,
                'email' => $email,
                'error' => $e,
            ]);

            if (strpos($e->getMessage(), 'Duplicate entry') > 0) {
                if (strpos($e->getMessage(), 'for key \'idx_user$phone\'') > 0) {
                    throw new PublicMessageException('Пользователь с телефоном "' . $phone . '" уже зарегистрирован в системе. ');
                }
                if (strpos($e->getMessage(), 'for key \'idx_user$email\'') > 0) {
                    throw new PublicMessageException('Пользователь с E-mail "' . $email . '" уже зарегистрирован в системе. ');
                }
                if (strpos($e->getMessage(), 'for key \'idx_user$login\'') > 0) {
                    throw new PublicMessageException('Пользователь  "' . $login . '" уже зарегистрирован в системе. ');
                }

                throw new PublicMessageException('Пользователь уже зарегистрирован в системе. ');
            }

            throw new PublicMessageException('Не удалось зарегистрировать пользователя. Ошибка в БД.');
        }

        //отправка email
        if (SEND_EMAIL_CONFIRM_REGISTRATION === true) {
            $body = 'Здравствуйте! Для подтверждения email перейдите по <a href="' . SITE_HOST . '/auth/confirmEmail/?code=' . $userModel->email_code . '">ссылке</a>.';
            SwiftMailer::sendEmailMessage([$userModel->email], 'Подтверждение регистрации от ' . SITE_HOST, $body);
        }

        //отправка СМС на телефон регистрируемого пользователя
        if (SEND_SMS_CONFIRM_REGISTRATION === true) {
            try {
                (new SmsSender)->sendViaSmsRu([$userModel->phone], $userModel->phone_code);
            } catch (PublicMessageException $e) {
                throw new PublicMessageException($e->getMessage());
            } catch (Exception $e) {
                BtLogger::getLogger()->error('Send SMS error.', [$e]);
                throw new PublicMessageException($e->getMessage());
            }
        }

        StatisticService::getInstance()->clearStatisticInSession();

        return true;
    }

    /**
     * Авторизация пользователя
     *
     * @param string $login логин пользователя
     * @param string $pass пароль пользователя
     * @param string $phone номер телефона
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function login($login, $phone, $pass)
    {
        if (empty($login) && empty($phone) || empty($pass)) {
            throw new PublicMessageException('Отсутствуют некоторые входные параметры.');
        }

        $userModel = $this->checkUser($login, (new PhoneHelper)->formatPhoneForSave($phone), $this->getHashPass($pass));

        $sessionId = session_id();
        if (empty($sessionId)) {
            session_start();
            StatisticService::getInstance()->createSession();
        }
        Yii::app()->session['user'] = [
            'id' => $userModel->id,
            'type' => $userModel->userType->name,
            'email' => $userModel->email,
            'login' => $userModel->login,
            'phone' => $userModel->phone,
            'phone_code' => $userModel->phone_code,
            'pass' => $userModel->pass,
        ];
        Yii::app()->session['authorized'] = true;
        StatisticService::getInstance()->clearStatisticInSession();
        OrderService::getInstance()->createFromBuf($userModel->id, $userModel->type);

        return true;
    }


    /**
     * Выход пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function logout()
    {
        $sessionStatId = $_SESSION["id"];
        $_SESSION = [];
        $_SESSION["id"] = $sessionStatId;
        setcookie('authToken', '', COOKIE_EXPIRES, '/');
        setcookie('userId', '', COOKIE_EXPIRES, '/');

        return true;
    }

    /**
     * Запомнить авторизацию пользователя в куки
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function rememberUser()
    {
        $currUser = Yii::app()->session['user'];
        if (empty($currUser)) {
            throw new PublicMessageException('Пользователь не авторизован.');
        }

        setcookie('userId', $currUser['id'], COOKIE_EXPIRES, '/');
        setcookie('authToken', $this->generateAuthToken(
            $currUser['phone'],
            $currUser['email'],
            $currUser['login'],
            $currUser['phone_code']
        ), COOKIE_EXPIRES, '/');

        return true;
    }

    /**
     * Авторизовать пользователя по кукам
     *
     * @param int $userId идентификатор пользователя
     * @param string $authToken токен авторизации
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function authUserFromCookie($userId, $authToken)
    {
        if (empty($_COOKIE['userId']) || empty($_COOKIE['authToken'])) {
            return false;
        }

        try {
            $currUser = UserModel::model()->with('userType')->findByPk($_COOKIE['userId']);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not auth user from cookies. Fatal error.', [
                'userId' => $_COOKIE['userId'],
                'authToken' => $_COOKIE['authToken'],
                'error' => $e,
            ]);

            return false;
        }
        if (empty($currUser)) {
            return false;
        }

        $validAuthToken = $this->generateAuthToken(
            $currUser->phone,
            $currUser->email,
            $currUser->login,
            $currUser->phone_code
        );

        if ($authToken !== $validAuthToken) {
            return false;
        }

        Yii::app()->session['user'] = [
            'id' => $currUser->id,
            'type' => $currUser->userType->name,
            'login' => $currUser->login,
            'phone' => $currUser->phone,
            'phone_code' => $currUser->phone_code,
            'pass' => $currUser->pass,
        ];
        Yii::app()->session['authorized'] = true;
        StatisticService::getInstance()->clearStatisticInSession();

        return true;
    }

    /**
     * Проверка пользователя на авторизацию
     *
     * @param string $login логин пользователя
     * @param string $pass пароль пользователя
     * @param string $phone номер телефона
     *
     * @return UserModel
     * @throws PublicMessageException
     */
    public function checkUser($login, $phone, $pass)
    {
        try {
            $searchAttributes = ['phone' => $phone];

            $userModel = UserModel::model()->with('userType')->findByAttributes($searchAttributes);
            if (empty($userModel)) {
                throw new PublicMessageException('Пользователь не найден.');
            }

            if (!hash_equals($userModel->pass, $pass)) {
                BtLogger::getLogger()->error('Pass not valid. Fatal error.', [
                    'db_pass' => $userModel->pass,
                    'insert_pass' => $pass
                ]);

                throw new PublicMessageException('Неверный пароль.');
            }

            if (SEND_SMS_CONFIRM_REGISTRATION === true) {
                $searchAttributes['confirm_phone'] = true;
                $userModel = UserModel::model()->findByAttributes($searchAttributes);
                if (empty($userModel)) {
                    throw new PublicMessageException('Пользователь не подтвержден. Необходимо <a href="/auth/phoneCode">ввести код подтверждения</a>.');
                }
            }
            if (SEND_EMAIL_CONFIRM_REGISTRATION === true) {
                $searchAttributes['confirm_email'] = true;
                $userModel = UserModel::model()->findByAttributes($searchAttributes);
                if (empty($userModel)) {
                    throw new PublicMessageException('Пользователь не подтвержден. Необходимо <a href="/auth/forgotPass">сменить пароль по E-Mail</a>.');
                }
            }

            return $userModel;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not authorize user. Fatal error.', [
                'login' => $login,
                'phone' => $phone,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось авторизоваться. Ошибка в БД.');
        }
    }

    /**
     * Алгоритм генерации токена авторизации
     *
     * @param string $login логин пользователя
     * @param string $email E-mail пользователя
     * @param string $phone номер телефона
     * @param string $salt соль для безопасности
     *
     * @return string
     */
    public function generateAuthToken($phone, $email, $login, $salt)
    {
        return md5(md5($phone) . md5($email) . md5($login) . md5($salt));
    }

    /**
     * Алгоритм генерации кода подтверждения по телефону
     *
     * @return int
     */
    public function generatePhoneCodeConfirm()
    {
        return rand(pow(10, 4), pow(10, 5) - 1);
    }

    /**
     * Алгоритм хеширования пароля пользователя
     *
     * @param string $pass пароль пользователя
     * @return string
     */
    public function getHashPass($pass)
    {
        return md5($pass . PASS_SALT);
    }

    /**
     * Проверка Админа
     *
     * @param integer $userId идентификатор польльзователя, которые должен быть с признаком админа
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function checkAccessByAdmin($userId)
    {
        if (!empty($userId)) {
            $userModel = UserModel::model()->with('userType')->findByAttributes([
                'id' => $userId,
                'admin_flag' => true,
            ]);
        } else {
            throw new PublicMessageException(ErrorEnum::USER_NOT_FOUND);
        }

        if (empty($userModel)) {
            return false;
        }

        return true;
    }

    /**
     * Получение пользователя для Админа
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userPhone телефон пользователя
     *
     * @return UserModel
     * @throws PublicMessageException
     */
    public function getUserByIdOrPhone($userId, $userPhone = null)
    {
        if (!empty($userPhone)) {
            $userModel = UserModel::model()->with('userType')->findByAttributes([
                'phone' => $userPhone,
            ]);
        } elseif (!empty($userId)) {
            $userModel = UserModel::model()->with('userType')->findByPk($userId);
        } else {
            throw new PublicMessageException('Пользователь не найден.');
        }

        if (empty($userModel)) {
            throw new PublicMessageException('Пользователь не найден.');
        }

        return $userModel;
    }

    /**
     * Отправка нового кода подтверждения
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function resendPhoneCodeConfirm()
    {
        try {
            (new SmsSender)->sendViaSmsRu([
                Yii::app()->session['user']['phone'],
            ],
                Yii::app()->session['user']['phone_code']
            );
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Send SMS error.', [$e]);
            throw new PublicMessageException($e->getMessage());
        }

        return true;
    }

    /**
     * Подтверждение пользователя по телефону
     *
     * @param integer $phoneCode код подтверждения
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function phoneCodeConfirm($phoneCode)
    {
        if (empty($phoneCode)) {
            BtLogger::getLogger()->error('Phone confirm code not exists.', [
                'phone_code' => $phoneCode,
            ]);
            throw new PublicMessageException('Отсутствует код подтверждения.');
        }

        try {
            $userModel = UserModel::model()->findByPk(Yii::app()->session['user']['id']);
            if (empty($userModel)) {
                BtLogger::getLogger()->error('User not exists.', [
                    'phone_code' => $phoneCode,
                ]);
                throw new PublicMessageException('Пользователь отсутствует в системе.');
            }
            if ($userModel->phone_code !== $phoneCode) {
                BtLogger::getLogger()->error('Phone confirm code is invalid.', [
                    'phone_code' => $phoneCode,
                    'user_id' => $userModel['id'],
                ]);
                throw new PublicMessageException('Неверный код подтверждения.');
            }

            $userModel->confirm_phone = true;
            if (!$userModel->save()) {
                BtLogger::getLogger()->error('Can not register user.', [
                    'phone_code' => $phoneCode,
                    'user_id' => $userModel['id'],
                    'error' => $userModel->getErrors(),
                ]);
                throw new PublicMessageException('Не удалось провести подтверждение регистрации.');
            }
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not register user. Fatal error.', [
                'phone_code' => $phoneCode,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось провести подтверждение регистрации.');
        }
    }

    /**
     * Отправка пароля на почту
     *
     * @param string $email почта пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function sendForgotPassword($email)
    {
        $userModel = UserModel::model()->findByAttributes(['email' => $email]);
        if (empty($userModel)) {
            BtLogger::getLogger()->error('User not exists.', [
                'phone_code' => $email,
            ]);
            throw new PublicMessageException('email ' . $email . ' отсутствует в системе.');
        }

        $body = 'Здравствуйте, ' . $userModel->login . '! Для изменения пароля перейдите по ' .
            '<a href="' . SITE_HOST . '/auth/changePassForm/?code=' . $userModel->email_code . '">ссылке</a>.';
        SwiftMailer::sendEmailMessage([$userModel->email], 'Подтверждение регистрации от ' . SITE_HOST, $body);
    }

    /**
     * Смена пароля пользователя
     *
     * @param string $pass новый пароль пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function changeUserPass($newPass, $linkCodeForChangePass)
    {
        $userModel = UserModel::model()->findByAttributes(['email_code' => $linkCodeForChangePass]);
        if (empty($userModel)) {
            BtLogger::getLogger()->error('User not exists.', [
                'newPass' => $newPass,
                'linkCodeForChangePass' => $linkCodeForChangePass,
            ]);
            throw new PublicMessageException('Смена пароля невозможна: попробуйте восстановить пароль повторно. ');
        }

        try {
            $userModel->pass = $this->getHashPass($newPass);
            if (!$userModel->save()) {
                BtLogger::getLogger()->error('Can not register user.', [
                    'userId' => $userModel->id,
                    'newPass' => $newPass,
                    'linkCodeForChangePass' => $linkCodeForChangePass,
                ]);
                throw new PublicMessageException('Не удалось сменить пароль пользователя.');
            }
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not register user. Fatal error.', [
                'userId' => $userModel->id,
                'newPass' => $newPass,
                'linkCodeForChangePass' => $linkCodeForChangePass,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось сменить пароль пользователя. Ошибка в БД.');
        }
    }

    /**
     * Отправка сообщений через Pusher
     *
     * @param string $message сообщение пользователя
     * @throws PublicMessageException
     */
    public function chatSendMess($message)
    {
        if (empty($message)) {
            throw new PublicMessageException('Сообщение не должно быть пустым.');
        }

        try {
            $pusher = new Pusher\Pusher(PUSHER_AUTH_KEY, PUSHER_SECRET_KEY, PUSHER_APP_ID, [
                    'cluster' => 'eu',
                    'useTLS' => true,
                ]
            );

            $data = [
                'userId' => Yii::app()->session['user']['id'],
                'login' => Yii::app()->session['user']['login'],
                'message' => $message,
            ];
            $pusher->trigger('bt-chat-channel', 'send-message-event', $data);

            BtChatLogger::getLogger()->info('Chat', [$data]);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not send chat message.', [
                'error' => $e,
            ]);
            throw new PublicMessageException('Не удалось отправить сообщение в чат.');
        }
    }

    /**
     * Возвращает всех пользователей, кроме админов
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getAllWithoutAdmin()
    {
        try {
            return (new UserDsp())->getAllWithoutAdmin();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get all users.', [
                'error' => $e,
            ]);
            throw new PublicMessageException('Не удалось получить всех пользователей.');
        }
    }

    /**
     * Подтверждение пользователя
     *
     * @param string $userId идентификатор пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function confirmPhone($userId)
    {
        $userModel = UserModel::model()->findByPk($userId);
        if (empty($userModel)) {
            BtLogger::getLogger()->error('User not exists.', [
                'userId' => $userId,
            ]);
            throw new PublicMessageException(ErrorEnum::USER_NOT_FOUND);
        }

        try {
            $userModel->confirm_phone = true;
            if (!$userModel->save()) {
                BtLogger::getLogger()->error('Can not confirm phone.', [
                    'userId' => $userModel->id,
                ]);
                throw new PublicMessageException('Ошибка подтверждения телефона.');
            }
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not confirm phone. Fatal error.', [
                'userId' => $userModel->id,
                'error' => $e,
            ]);

            throw new PublicMessageException('Ошибка подтверждения телефона. Ошибка в БД.');
        }
    }
}
