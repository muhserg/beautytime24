<?php

/**
 * Информация
 */

class InfoController extends Controller
{
    /**
     * Страница 'Карта'
     */
    public function actionMap()
    {
        echo (new TemplateEngineTwig)->render('info/Map.twig');
    }

    /**
     * Страница О нас
     */
    public function actionContacts()
    {
        echo (new TemplateEngineTwig)->render('info/Contacts.twig', [
            'techPhoneImg' => ImageHelper::createImageFromString(
                SERVICE_DESC_PHONE,
                (isset(Yii::app()->session['isMobile']) ? Yii::app()->session['isMobile'] : false),
                ImgLabelTypeEnum::LONG_TEXT
            ),
            'emailInfoImg2' => ImageHelper::createImageFromString(
                'info@' . SITE_NAME . '.ru',
                (isset(Yii::app()->session['isMobile']) ? Yii::app()->session['isMobile'] : false),
                ImgLabelTypeEnum::LONG_TEXT
            ),
            'youAboutCompanyUrl' => ABOUT_COMPANY_YOUTUBE_URL,
        ]);
    }

    /**
     * Страница FAQ
     */
    public function actionFaq()
    {
        echo (new TemplateEngineTwig)->render('info/Faq.twig', [
            'youAboutCompanyUrl' => ABOUT_COMPANY_YOUTUBE_URL,
        ]);
    }

    /**
     * Страница с инфой об оплате
     */
    public function actionPay()
    {
        echo (new TemplateEngineTwig)->render('info/Pay.twig', [
        ]);
    }

    /**
     * Правила сервиса
     */
    public function actionRules()
    {
        echo (new TemplateEngineTwig)->render('info/Rules.twig');
    }

    /**
     * Отзывы мастеров о сервисе
     */
    public function actionMasterReviews()
    {
        echo (new TemplateEngineTwig)->render('info/MasterReviews.twig');
    }

    /**
     * Отзывы салонов о сервисе
     */
    public function actionSalonReviews()
    {
        echo (new TemplateEngineTwig)->render('info/SalonReviews.twig');
    }

    /**
     * Оферта
     */
    public function actionOffer()
    {
        echo (new TemplateEngineTwig)->render('info/Offer.twig');
    }

    /**
     * Частые вопр клиентов
     */
    public function actionClientFaq()
    {
        echo (new TemplateEngineTwig)->render('info/ClientFaq.twig');
    }

    /**
     * Частые вопр мастеров
     */
    public function actionMasterFaq()
    {
        echo (new TemplateEngineTwig)->render('info/MasterFaq.twig');
    }

    /**
     * Частые вопр салонов
     */
    public function actionSalonFaq()
    {
        echo (new TemplateEngineTwig)->render('info/SalonFaq.twig', [
            'profileDirections' => RubricatorService::getInstance()->getDirections(),
        ]);
    }

    //==============================================================================================

    /**
     * Инструкция для клиентов
     */
    public function actionSalonInstruction()
    {
        try {
            echo (new TemplateEngineTwig)->render('info/SalonInstruction.twig', [
                'showNavbar' => false,
                'loginControlTextStyle' => true,
                'showTopHelpLinks' => false,
                'showBrandBtNotice' => false,
                'directions' => RubricatorService::getInstance()->getDirections(false),
                'services' => RubricatorService::getInstance()->getServices(),
                'rand5Orders' => OrderService::getInstance()->rand5Orders(),
                'youBtReviewUrls' => ReviewService::getInstance()->getRandVideoReviews(3),
                'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                'youAboutCompanyUrl' => ABOUT_COMPANY_YOUTUBE_URL,
            ]);
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('info/SalonInstruction.twig', [
                'errorText' => $e->getMessage(),
                'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                'youAboutCompanyUrl' => ABOUT_COMPANY_YOUTUBE_URL,
            ]);
            Yii::app()->end();
        }
    }

    /**
     * Устаревшая инструкция для клиентов
     */
    public function actionClientInstruction()
    {
        try {
            $masters = [];
            $masterModels = SearchService::getInstance()->randMasters(SEARCH_RAND_COUNT);
            foreach ($masterModels as $num => $masterModel) {
                $masters[] = array_merge($masterModel, [
                    'img_class' => ($masterModel['avatarUrl'] !== IMG_PATH_NO_AVATAR ? 'round_photo' : 'round_no_photo'),
                ]);
            }

            $reviews = ReviewService::getInstance()->randReview(SEARCH_RAND_COUNT);
            foreach ($reviews as $num => $review) {
                $reviews[$num]['img_class'] = ($review['avatarUrl'] !== IMG_PATH_NO_AVATAR ? 'round_photo' : 'round_no_photo');
            }
            echo (new TemplateEngineTwig)->render(
                'info/ClientInstruction.twig', [
                'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                'masters' => $masters,
                'reviews' => $reviews
            ]);
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'info/ClientInstruction.twig', [
                'errorText' => $e->getMessage(),
                'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                'masters' => $masters,
            ]);
            Yii::app()->end();
        }
    }


    /**
     * Частые вопр мастеров
     */
    public function actionMasterInstruction()
    {
        try {
            echo (new TemplateEngineTwig)->render('info/MasterInstruction.twig', [
                'rand5Orders' => OrderService::getInstance()->rand5Orders(),
                'vacancies' => VacancyService::getInstance()->randVacancies(SEARCH_RAND_COUNT)
            ]);
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('info/MasterInstruction.twig', [
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }
    }

    /**
     * Частые вопр салонов
     */
    public function actionSalonInstructionOld()
    {
        echo (new TemplateEngineTwig)->render('info/SalonInstructionOld.twig', [
        ]);
    }
}
