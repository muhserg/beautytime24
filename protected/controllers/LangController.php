<?php

/**
 * Смена языка сайта
 */

class LangController extends Controller
{
    /**
     * Смена языка сайта
     */
    public function actionChange()
    {
        $currLang = isset(Yii::app()->session['language']) ? Yii::app()->session['language'] : 'ru';
        Yii::app()->session['language'] = ($currLang === 'ru' ? 'en' : 'ru');

        Yii::app()->request->redirect('/');
    }
}
