<?php

/**
 * Доработанный класс контроллера с проверкой авторизации
 */
class BtController extends Controller
{
    public function beforeAction($action)
    {
        //если стояла галочка 'Запомнить меня'
        try {
            if (!empty($_COOKIE['userId']) && !empty($_COOKIE['authToken'])
                && UserService::getInstance()->authUserFromCookie($_COOKIE['userId'], $_COOKIE['authToken']) === true) {
                return parent::beforeAction($action);
            }
        } catch (PublicMessageException $e) {
        }

        $user = Yii::app()->session['user'];
        if (empty($user)) {
            //отправка на форму авторизации
            Yii::app()->request->redirect('/auth');
        } else {
            try {
                UserService::getInstance()->checkUser($user['login'], $user['phone'], $user['pass']);
            } catch (PublicMessageException $e) {
                Yii::app()->session['authorized'] = false;
                echo (new TemplateEngineTwig)->render('Auth.twig', ['errorText' => $e->getMessage()]);
                Yii::app()->end();
            }
        }

        Yii::app()->session['authorized'] = true;
        return parent::beforeAction($action);
    }
}
