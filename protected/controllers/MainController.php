<?php

/**
 * Основные страницы сайта
 */

class MainController extends Controller
{
    /**
     * Главная (первая) страница
     */
    public function actionIndex()
    {
        try {
            echo (new TemplateEngineTwig)->render('Index.twig', [
                'showNavbar' => true,
                'directions' => RubricatorService::getInstance()->getDirections(false),
                'services' => RubricatorService::getInstance()->getServices(),
                'rand5Orders' => OrderService::getInstance()->rand5Orders(),
                'youBtReviewUrls' => ReviewService::getInstance()->getRandVideoReviews(3),
                'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                'youAboutCompanyUrl' => ABOUT_COMPANY_YOUTUBE_URL,
            ]);
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('Index.twig', [
                'errorText' => $e->getMessage(),
                'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                'youAboutCompanyUrl' => ABOUT_COMPANY_YOUTUBE_URL,
            ]);
            Yii::app()->end();
        }
    }


    /**
     * Главная (первая) страница для клиентов
     */
    public function actionClients()
    {
        try {
            echo (new TemplateEngineTwig)->render('Index.twig', [
                'showNavbar' => false,
                'directions' => RubricatorService::getInstance()->getDirections(false),
                'services' => RubricatorService::getInstance()->getServices(),
                'rand5Orders' => OrderService::getInstance()->rand5Orders(),
                'youBtReviewUrls' => ReviewService::getInstance()->getRandVideoReviews(3),
                'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                'youAboutCompanyUrl' => ABOUT_COMPANY_YOUTUBE_URL,
            ]);
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('Index.twig', [
                'errorText' => $e->getMessage(),
                'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                'youAboutCompanyUrl' => ABOUT_COMPANY_YOUTUBE_URL,
            ]);
            Yii::app()->end();
        }
    }

    /**
     * Обратная связь
     */
    public function actionFeedBack()
    {
        echo (new TemplateEngineTwig)->render('FeedBack.twig', [
            'senderName' => Yii::app()->session['user']['login'],
            'phone' => Yii::app()->session['user']['phone'],
            'email' => !empty(Yii::app()->session['user']['email']) ? Yii::app()->session['user']['email'] : null,
        ]);
    }

    //====================================================================================================


    /**
     * Отправка сообщения обратной связи
     *
     * @return string
     */
    public function actionSendFeedBack()
    {
        try {
            FeedBackService::getInstance()->sendFeedBack(
                Yii::app()->request->getParam('sender-name', ''),
                Yii::app()->request->getParam('phone-number', ''),
                Yii::app()->request->getParam('email', ''),
                Yii::app()->request->getParam('comment', '')
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('FeedBack.twig', [
                'senderName' => Yii::app()->request->getParam('sender-name'),
                'phone' => Yii::app()->request->getParam('phone-number'),
                'email' => Yii::app()->request->getParam('email'),
                'comment' => Yii::app()->request->getParam('comment', ''),
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }

        Yii::app()->request->redirect('/');
    }

    /**
     * Создание предварительного заказа
     *
     * @return string
     */
    public function actionCreateBufOrder()
    {
        try {
            //если авторизованы - сразу создаем нормальный заказ
            if (isset(Yii::app()->session['authorized']) && Yii::app()->session['authorized'] === true) {
                $orderId = OrderService::getInstance()->create(
                    Yii::app()->session['user']['id'],
                    Yii::app()->session['user']['type'],
                    Yii::app()->request->getParam('client-services'),
                    Yii::app()->request->getParam('order-date'),
                    null,
                    Yii::app()->request->getParam('client-address'),
                    Yii::app()->request->getParam('client-address-coord'),
                    Yii::app()->request->getParam('order-about'),
                    Yii::app()->request->getParam('order-cost'),
                    Yii::app()->request->getParam('order-place'),
                    Yii::app()->request->getParam('order-time'),
                    $_FILES['order-photo-file']
                );

                if (!empty($orderId)) {
                    Yii::app()->request->redirect('/lk/thanks');
                } else {
                    echo (new TemplateEngineTwig)->render('Index.twig', [
                        'errorText' => 'Ошибка создания заказа.',
                    ]);
                    Yii::app()->end();
                }
            }

            Yii::app()->session['guestPhone'] = Yii::app()->request->getParam('phone-number');
            Yii::app()->session['guestName'] = Yii::app()->request->getParam('user-name');

            if (OrderService::getInstance()->createBuf(
                    session_id(),
                    Yii::app()->request->getParam('client-services'),
                    Yii::app()->request->getParam('order-date'),
                    Yii::app()->request->getParam('client-address'),
                    Yii::app()->request->getParam('client-address-coord'),
                    Yii::app()->request->getParam('order-about'),
                    Yii::app()->request->getParam('order-cost'),
                    Yii::app()->request->getParam('order-place'),
                    Yii::app()->request->getParam('order-time'),
                    $_FILES['order-photo-file']
                ) === false) {
                echo (new TemplateEngineTwig)->render('Index.twig', [
                    'errorText' => 'Ошибка создания предварительного заказа.',
                ]);
            }

            Yii::app()->request->redirect('/lk/myOrders');
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('Index.twig', [
                'clientServices' => Yii::app()->request->getParam('client-services'),
                'orderDate' => Yii::app()->request->getParam('order-date'),
                'orderTime' => Yii::app()->request->getParam('order-time'),
                'clientAddress' => Yii::app()->request->getParam('client-address'),
                'clientAddressCoord' => Yii::app()->request->getParam('client-address-coord'),
                'orderAbout' => Yii::app()->request->getParam('order-about'),
                'orderCost' => Yii::app()->request->getParam('order-cost'),
                'orderPlace' => Yii::app()->request->getParam('order-place'),
                'login' => Yii::app()->request->getParam('user-name'),
                'phone' => Yii::app()->request->getParam('phone-number'),

                'directions' => RubricatorService::getInstance()->getDirections(false),
                'services' => RubricatorService::getInstance()->getServices(),
                'rand5Orders' => OrderService::getInstance()->rand5Orders(),
                'youBtReviewUrls' => ReviewService::getInstance()->getRandVideoReviews(3),
                'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }
    }

}
