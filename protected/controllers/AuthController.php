<?php

/**
 * Авторизация пользователя
 */

class AuthController extends Controller
{
    /**
     * Страница авторизации
     *
     * @return string
     */
    public function actionIndex()
    {
        //если стояла галочка 'Запомнить меня'
        try {
            if (!empty($_COOKIE['userId']) && !empty($_COOKIE['authToken'])
                && UserService::getInstance()->authUserFromCookie($_COOKIE['userId'], $_COOKIE['authToken']) === true) {
                Yii::app()->request->redirect('/lk/myOrders');
            }
        } catch (PublicMessageException $e) {
        }

        $user = Yii::app()->session['user'];
        if (empty($user) || empty($user['id']) || empty($user['type'])) {
            echo (new TemplateEngineTwig)->render('Auth.twig', [
                'phone' => Yii::app()->session['guestPhone'],
            ]);
            Yii::app()->end();
        } else {
            try {
                UserService::getInstance()->checkUser(
                    $user['login'],
                    $user['phone'],
                    UserService::getInstance()->getHashPass($user['pass'])
                );
            } catch (PublicMessageException $e) {
                Yii::app()->session['authorized'] = false;
                echo (new TemplateEngineTwig)->render('Auth.twig', [
                    'errorText' => $e->getMessage(),
                    'phone' => Yii::app()->session['guestPhone'],
                ]);
                Yii::app()->end();
            }

            Yii::app()->session['authorized'] = true;
            Yii::app()->request->redirect('/lk/myOrders');
        }
    }

    /**
     * Страница 'забыли пароль'
     *
     * @return string
     */
    public function actionForgotPassword()
    {
        echo (new TemplateEngineTwig)->render('ForgotPass.twig');
    }

    /**
     * Страница регистрации в системе
     *
     * @return string
     */
    public function actionRegister()
    {
        echo (new TemplateEngineTwig)->render('Register.twig', [
            'phone' => Yii::app()->session['guestPhone'],
            'login' => Yii::app()->session['guestName'],
        ]);
    }

    /**
     * Страница ввода телефонного кода
     *
     * @return string
     */
    public function actionPhoneCode()
    {
        echo (new TemplateEngineTwig)->render('PhoneCode.twig');
    }

    /**
     * Страница смены пароля
     *
     * @param string $code проверочный код, присутствующий в ссылке на сайт в письме пользователю о смене пароля
     * @return string
     */
    public function actionChangePassForm($code)
    {
        echo (new TemplateEngineTwig)->render('ChangePass.twig', ['linkCodeForChangePass' => $code]);
    }

    //====================================================================================================

    /**
     * Авторизация в системе
     */
    public function actionLogin()
    {
        try {
            UserService::getInstance()->login(
                trim(Yii::app()->request->getParam('user-name')),
                Yii::app()->request->getParam('phone-number'),
                trim(Yii::app()->request->getParam('password'))
            );

            if (Yii::app()->request->getParam('remember-me') === 'on') {
                UserService::getInstance()->rememberUser();
            }
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('Auth.twig', [
                'phone' => Yii::app()->request->getParam('phone-number'),
                'rememberMe' => Yii::app()->request->getParam('remember-me') === 'on' ? 'checked' : '',
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }

        if (!empty(Yii::app()->session['guestOrderId'])) {
            Yii::app()->request->redirect('/lk/order/?id=' . Yii::app()->session['guestOrderId']);
        }

        $userType = isset(Yii::app()->session['user']['type']) ? Yii::app()->session['user']['type'] : null;
        if ($userType === UserTypeEnum::MASTER || $userType === UserTypeEnum::SALON) {
            Yii::app()->request->redirect('/lk/viewProfile');
        } else {
            Yii::app()->request->redirect('/lk/myOrders');
        }
    }

    public function actionLogout()
    {
        try {
            UserService::getInstance()->logout();
        } catch (PublicMessageException $e) {
            echo $e->getMessage();
        }

        Yii::app()->request->redirect('/');
    }

    /**
     * Регистрация в системе
     *
     * @return string
     */
    public function actionRegistration()
    {
        try {
            UserService::getInstance()->register(
                trim(Yii::app()->request->getParam('user-name', '')),
                trim(Yii::app()->request->getParam('password', '')),
                Yii::app()->request->getParam('user-type', ''),
                Yii::app()->request->getParam('phone-number', ''),
                Yii::app()->request->getParam('email', '')
            );
        } catch (PublicMessageException $e) {
            if ($e->getMessage() === 'Не удалось отправить СМС. Пустой ответ.') {
                Yii::app()->request->redirect('/auth/phoneCode');
            }

            echo (new TemplateEngineTwig)->render('Register.twig', [
                'login' => Yii::app()->request->getParam('user-name'),
                'phone' => Yii::app()->request->getParam('phone-number'),
                'email' => Yii::app()->request->getParam('email'),
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }

        if (SEND_SMS_CONFIRM_REGISTRATION === true) {
            Yii::app()->request->redirect('/auth/phoneCode');
        }

        if (Yii::app()->session['user']['type'] === UserTypeEnum::MASTER
            || Yii::app()->session['user']['type'] === UserTypeEnum::SALON
            || Yii::app()->session['user']['type'] === UserTypeEnum::CLIENT) {
            Yii::app()->request->redirect('/lk/profile');
        } else {
            Yii::app()->request->redirect('/lk/myOrders');
        }
    }

    /**
     * Подтверждение пользователя по телефону
     *
     * @return string
     */
    public function actionPhoneCodeConfirm()
    {
        try {
            UserService::getInstance()->phoneCodeConfirm(
                Yii::app()->request->getParam('phone-code', '')
            );
            Yii::app()->session['authorized'] = true;
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('PhoneCode.twig', ['errorText' => $e->getMessage()]);
            Yii::app()->end();
        }

        if (Yii::app()->session['user']['type'] === UserTypeEnum::MASTER) {
            Yii::app()->request->redirect('/lk/profile');
        } else {
            Yii::app()->request->redirect('/lk/myOrders');
        }
    }

    /**
     * Страница 'Забыли пароль' - отправка письма на почту
     *
     * @return string
     */
    public function actionSendForgotPassword()
    {
        try {
            UserService::getInstance()->sendForgotPassword(Yii::app()->request->getParam('email', ''));
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('ForgotPass.twig', [
                'email' => Yii::app()->request->getParam('email', ''),
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }

        Yii::app()->request->redirect('/auth');
    }


    /**
     * Смена пароля пользователя
     *
     * @return string
     */
    public function actionChangePass()
    {
        try {
            UserService::getInstance()->changeUserPass(
                Yii::app()->request->getParam('password', ''),
                Yii::app()->request->getParam('linkCodeForChangePass', '')
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('ChangePass.twig', [
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }

        Yii::app()->request->redirect('/auth');
    }

    //Ajax ============================================================================

    /**
     * Отправка нового кода подтверждения
     *
     * @return string
     */
    public function actionResendPhoneCodeConfirm()
    {
        try {
            if (UserService::getInstance()->resendPhoneCodeConfirm() === true) {
                $this->response('Код подтверждения повторно отправлен на Ваш телефон.');
            }
        } catch (PublicMessageException $e) {
            $this->response('Ошибка отправки: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }
}
