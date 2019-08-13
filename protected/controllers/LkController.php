<?php

/**
 * Личный кабинет пользователя
 */

class LkController extends BtController
{
    protected $lkTwigContext = [];

    protected $userId = null;
    protected $userType = null;

    public function beforeAction($action)
    {
        if (!empty($action->id) && $action->id === 'roboResult') {
            return true;
        }

        $this->userId = isset(Yii::app()->session['user']['id']) ? Yii::app()->session['user']['id'] : null;
        $this->userType = isset(Yii::app()->session['user']['type']) ? Yii::app()->session['user']['type'] : null;
        if (empty($this->userId) || empty($this->userType)) {
            //отправка на форму авторизации
            Yii::app()->request->redirect('/auth');
        }

        //заполняем значения переменных Twig, которые нужны на любой странице личного кабинета
        try {
            $this->lkTwigContext['profileDirectionsMenu'] = RubricatorService::getInstance()->getDirections();
            $this->lkTwigContext['profileIsComplete'] = ProfileService::getInstance()->isComplete(
                $this->userId,
                $this->userType
            );

            //список заказов в меню
            $ordersMenu = [];
            if (SHOW_ORDERS_IN_MENU === true) {
                if ($this->userType === UserTypeEnum::CLIENT) {
                    $ordersMenu = OrderService::getInstance()->getMyOrders($this->userId, $this->userType);
                } elseif ($this->userType === UserTypeEnum::MASTER) {
                    $ordersMenu = OrderService::getInstance()->getNotAgreeOrdersByMaster($this->userId,
                        $this->userType);
                } elseif ($this->userType === UserTypeEnum::SALON) {
                    $ordersMenu = OrderService::getInstance()->getNotAgreeOrdersBySalon($this->userId,
                        $this->userType);
                }
            }
            $this->lkTwigContext['userIsAdmin'] = UserService::getInstance()->checkAccessByAdmin($this->userId);
            $this->lkTwigContext['showOrdersInMenu'] = SHOW_ORDERS_IN_MENU;
            $this->lkTwigContext['myOrdersMenu'] = $ordersMenu;
        } catch (PublicMessageException $e) {
            $this->lkTwigContext['errorText'] = $e->getMessage();
        }
        $this->lkTwigContext['apiBankToken'] = BANK_API_TOKEN;

        return parent::beforeAction($action);
    }

    /**
     * Первая страница личного кабинета
     */
    public function actionIndex()
    {
        try {
            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/IndexLk.twig',
                array_merge([], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/IndexLk.twig', [
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }
    }

    /**
     * Cтраница Спасибо личного кабинета (после создания заказа)
     */
    public function actionThanks()
    {
        try {
            echo (new TemplateEngineTwig)->render('lk/Thanks.twig',
                array_merge([], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('lk/Thanks.twig', [
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }
    }


    /**
     * Cтраница Обучения
     */
    public function actionStudy()
    {
        try {
            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/Study.twig',
                array_merge([], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/Study.twig', [
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }
    }

    /**
     * Cтраница Видео уроков
     */
    public function actionStudyVideo()
    {
        try {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/StudyVideo.twig',
                array_merge([
                    'videos' => StudyService::getInstance()->getVideos($this->userId),
                    'directions' => RubricatorService::getInstance()->getDirections(false),
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/StudyVideo.twig', [
                'directions' => [],
                'videos' => [],
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }
    }

    /**
     * Чат пользователей Pusher
     */
    public function actionChat()
    {
        echo (new TemplateEngineTwig)->render(
            'lk/ChatPusher.twig',
            array_merge([
                'pusherAuthKey' => PUSHER_AUTH_KEY,
            ], $this->lkTwigContext));
    }


    /**
     * Страница создания заказа
     *
     * @param integer $masterId идентификатор профиля выбранного в поиске мастера (если заказ создается из поиска мастера)
     */
    public function actionCreateOrderPage($masterId = null)
    {
        try {
            $userProfileModel = ProfileService::getInstance()->getProfileModel(null, $this->userType, $this->userId);
        } catch (PublicMessageException $e) {
        }

        try {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/CreateOrder.twig',
                array_merge([
                    'directions' => RubricatorService::getInstance()->getDirections(false),
                    'services' => RubricatorService::getInstance()->getServices(),
                    'searchCharCount' => SEARCH_CHAR_COUNT,
                    'btnCreateOrderColor' => 'btn-info',

                    'clientAddress' => !empty($userProfileModel) ? $userProfileModel->address : '',
                    'clientAddressCoord' => !empty($userProfileModel) ? $userProfileModel->address_coord : '',
                    /*для Twig компонента contentFindMaster*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'masterBufProfileId' => $masterId,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/CreateOrder.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'searchCharCount' => SEARCH_CHAR_COUNT,
                    'btnCreateOrderColor' => 'btn-info',

                    'clientAddress' => !empty($userProfileModel) ? $userProfileModel->address : '',
                    'clientAddressCoord' => !empty($userProfileModel) ? $userProfileModel->address_coord : '',
                    /*для Twig компонента contentFindMaster*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                ], $this->lkTwigContext)
            );
        }
    }

    /**
     * Страница поиска мастера
     */
    public function actionFindMasterPage()
    {
        try {
            $userModel = ProfileService::getInstance()->getProfileModel(null, $this->userType, $this->userId);
            if (empty($userModel)) {
                throw new PublicMessageException('Заполните свой профиль.');
            }

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/FindMaster.twig',
                array_merge([
                    'directions' => RubricatorService::getInstance()->getDirections(),
                    'subways' => SearchService::getInstance()->getSubways(),
                    'address' => $userModel->address,
                    'addressCoord' => $userModel->address_coord,
                    'btnCreateOrderColor' => 'btn-pink',

                    /*для Twig компонента contentFindMaster*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'yandexGeoZoom' => isset($userModel->address_coord) ? YANDEX_GEOCODER_ADDRESS_ZOOM : YANDEX_GEOCODER_ZOOM,
                    'yandexGeoCoord' => isset($userModel->address_coord) ? $userModel->address_coord : YANDEX_GEOCODER_COORD,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/Error.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'btnCreateOrderColor' => 'btn-info',

                    /*для Twig компонента contentFindMaster*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'yandexGeoZoom' => YANDEX_GEOCODER_ZOOM,
                    'yandexGeoCoord' => YANDEX_GEOCODER_COORD,
                ], $this->lkTwigContext)
            );
        }
    }


    /**
     * Страница поиска салона
     */
    public function actionFindSalonPage()
    {
        try {
            $userModel = ProfileService::getInstance()->getProfileModel(null, $this->userType, $this->userId);
            if (empty($userModel)) {
                throw new PublicMessageException('Заполните свой профиль.');
            }

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/FindSalon.twig',
                array_merge([
                    'directions' => RubricatorService::getInstance()->getDirections(),
                    'subways' => SearchService::getInstance()->getSubways(),
                    'address' => $userModel->address,
                    'addressCoord' => $userModel->address_coord,
                    'btnCreateOrderColor' => 'btn-pink',

                    /*для Twig компонента contentFindMaster*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'yandexGeoZoom' => isset($userModel->address_coord) ? YANDEX_GEOCODER_ADDRESS_ZOOM : YANDEX_GEOCODER_ZOOM,
                    'yandexGeoCoord' => isset($userModel->address_coord) ? $userModel->address_coord : YANDEX_GEOCODER_COORD,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/Error.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'btnCreateOrderColor' => 'btn-info',

                    /*для Twig компонента contentFindMaster*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'yandexGeoZoom' => YANDEX_GEOCODER_ZOOM,
                    'yandexGeoCoord' => YANDEX_GEOCODER_COORD,
                ], $this->lkTwigContext)
            );
        }
    }


    /**
     * Страница поиска школы
     */
    public function actionFindSchoolPage()
    {
        try {
            $userModel = ProfileService::getInstance()->getProfileModel(null, $this->userType, $this->userId);
            if (empty($userModel)) {
                throw new PublicMessageException('Заполните свой профиль.');
            }

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/FindSchool.twig',
                array_merge([
                    'directions' => RubricatorService::getInstance()->getDirections(),
                    'subways' => SearchService::getInstance()->getSubways(),
                    'address' => $userModel->address,
                    'addressCoord' => $userModel->address_coord,
                    'btnCreateOrderColor' => 'btn-pink',

                    /*для Twig компонента contentFindMaster*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'yandexGeoZoom' => isset($userModel->address_coord) ? YANDEX_GEOCODER_ADDRESS_ZOOM : YANDEX_GEOCODER_ZOOM,
                    'yandexGeoCoord' => isset($userModel->address_coord) ? $userModel->address_coord : YANDEX_GEOCODER_COORD,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/Error.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'btnCreateOrderColor' => 'btn-info',

                    /*для Twig компонента contentFindMaster*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'yandexGeoZoom' => YANDEX_GEOCODER_ZOOM,
                    'yandexGeoCoord' => YANDEX_GEOCODER_COORD,
                ], $this->lkTwigContext)
            );
        }
    }


    /**
     * Страница создания акции
     */
    public function actionCreatePromotionPage()
    {
        if ($this->userType === UserTypeEnum::CLIENT) {
            echo (new TemplateEngineTwig)->render(
                'lk/Error.twig',
                array_merge([
                    'errorText' => 'Клиент не может создавать акции. Зарегистрируйтесь в качестве мастера или салона.',
                ], $this->lkTwigContext)
            );
        }

        try {
            $contextForm = [];

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/CreatePromotion.twig',
                array_merge([
                    'photoLabel' => 'Фото акции:<span class="text-danger">*</span>',
                    'aboutLabel' => 'Условия акции:<span class="text-danger">*</span>',

                    /*для Twig компонента photoProfile*/
                    'isRequiredUploadPhoto' => true,

                    /*для Twig компонента addressProfile*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'yandexGeoZoom' => isset($contextForm['addressCoord']) ? YANDEX_GEOCODER_ADDRESS_ZOOM : YANDEX_GEOCODER_ZOOM,
                    'yandexGeoCoord' => isset($contextForm['addressCoord']) ? $contextForm['addressCoord'] : YANDEX_GEOCODER_COORD,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/CreatePromotion.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'photoLabel' => 'Фото акции:<span class="text-danger">*</span>',
                    'aboutLabel' => 'Условия акции:<span class="text-danger">*</span>',
                    /*для Twig компонента photoProfile*/
                    'isRequiredUploadPhoto' => true,
                ], $this->lkTwigContext)
            );
        }
    }

    /**
     * Страница создания вакансии
     */
    public function actionCreateVacancyPage()
    {
        if ($this->userType === UserTypeEnum::CLIENT) {
            echo (new TemplateEngineTwig)->render(
                'lk/Error.twig',
                array_merge([
                    'errorText' => 'Клиент не может создавать вакансии. Зарегистрируйтесь в качестве салона.',
                ], $this->lkTwigContext)
            );
        }

        try {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/CreateVacancy.twig',
                array_merge([
                    'directions' => RubricatorService::getInstance()->getDirections(),
                    'weekdays' => ScheduleService::getInstance()->getWeekDaysBase(),
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/CreateVacancy.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
        }
    }

    /**
     * Страница создания школы
     */
    public function actionCreateSchoolPage()
    {
        try {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/CreateSchool.twig',
                array_merge([
                    'directions' => RubricatorService::getInstance()->getDirections(),
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/CreateSchool.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
        }
    }

    /**
     * Оплата через Сбербанк
     * https://3dsec.sberbank.ru/demopayment/docsite/pay-button.html
     *
     * Для пробных покупок используйте карту 411111111111111 / Срок действия: 12/19  / CVV: 123
     */
    public function actionSberPay()
    {
        echo (new TemplateEngineTwig)->render(
            'lk/SberPay.twig',
            array_merge([], $this->lkTwigContext)
        );
    }

    /**
     * Портфолио мастера или салона
     */
    public function actionPortfolio()
    {
        if ($this->userType === UserTypeEnum::CLIENT) {
            echo (new TemplateEngineTwig)->render(
                'lk/Error.twig',
                array_merge([
                    'errorText' => 'Не найдено портфолио.',
                ], $this->lkTwigContext)
            );
        }

        $workPhotos = [];

        try {
            $workPhotos = PortfolioService::getInstance()->getPortfolio($this->userId, $this->userType);
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/Portfolio.twig', [
                'workPhotos' => $workPhotos,
                'errorText' => $e->getMessage(),

                /*для Twig компонента photoProfile*/
                'isRequiredUploadPhoto' => true,
            ]);
            Yii::app()->end();
        }

        echo (new TemplateEngineTwig)->render(
            'lk/' . $this->userType . '/Portfolio.twig',
            array_merge([
                'workPhotos' => $workPhotos,

                /*для Twig компонента photoProfile*/
                'isRequiredUploadPhoto' => true,
            ], $this->lkTwigContext)
        );
    }

    /**
     * Портфолио мастера или салона выбранного админом пользователя
     * @param integer $userId идентификатор пользователя
     */
    public function actionPortfolioByAdmin($userId)
    {
        $workPhotos = [];

        try {
            if (UserService::getInstance()->checkAccessByAdmin($this->userId) === false) {
                throw new PublicMessageException(ErrorEnum::ACCESS_DENIED);
            }

            $userModel = UserModel::model()->findByPk($userId);
            if (empty($userModel->userType->name) || $userModel->userType->name === UserTypeEnum::CLIENT) {
                echo (new TemplateEngineTwig)->render(
                    'lk/Error.twig',
                    array_merge([
                        'errorText' => 'Не найдено портфолио для данного пользователя.',
                    ], $this->lkTwigContext)
                );
            }

            $workPhotos = PortfolioService::getInstance()->getPortfolio($userId, $userModel->userType->name);
            echo (new TemplateEngineTwig)->render(
                'lk/' . $userModel->userType->name . '/Portfolio.twig',
                array_merge([
                    'byAdmin' => true,
                    'uId' => $userModel->id,
                    'uType' => $userModel->userType->name,
                    'uLogin' => $userModel->login,
                    'workPhotos' => $workPhotos,

                    /*для Twig компонента photoProfile*/
                    'isRequiredUploadPhoto' => true,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('lk/' . $userModel->userType->name . '/Portfolio.twig', [
                'workPhotos' => $workPhotos,
                'errorText' => $e->getMessage(),

                /*для Twig компонента photoProfile*/
                'isRequiredUploadPhoto' => true,
            ]);
            Yii::app()->end();
        }
    }


    /**
     * Чат пользователей от morozovsk
     */
    public function actionChatSimple()
    {
        echo (new TemplateEngineTwig)->render(
            'lk/Chat.twig',
            array_merge([
                'webSocketHost' => WEB_SOCKET_CLIENT_HOST,
            ], $this->lkTwigContext));
    }


    /**
     * Профиль пользователя (просмотр)
     */
    public function actionViewProfile()
    {
        try {
            $profileContext = [
                'profilesForModerate' => ProfileService::getInstance()->getForModerate($this->userId, $this->userType),

                'viewInProfile' => VIEW_IN_PROFILE,
                /*для Twig компонента addressProfile*/
                'yandexGeoKey' => YANDEX_GEOCODER_KEY,
            ];

            $profileContext = array_merge(
                $profileContext,
                ProfileService::getInstance()->getProfile($this->userId, $this->userType, 'rand()')
            );

            if ($this->userType === UserTypeEnum::CLIENT) {
                $profileContext['reviews'] = ReviewService::getInstance()->getByUser($this->userId,
                    REVIEWS_PROFILE_COUNT);
            } elseif ($this->userType === UserTypeEnum::MASTER) {
                $profileContext['reviews'] = ReviewService::getInstance()->getByMaster(
                    $this->userId,
                    REVIEWS_PROFILE_COUNT
                );
                $profileContext['workPhotos'] = PortfolioService::getInstance()->getPortfolio(
                    $this->userId,
                    $this->userType
                );
            } else {
                $profileContext['workPhotos'] = PortfolioService::getInstance()->getPortfolio(
                    $this->userId,
                    $this->userType
                );
                $profileContext['reviews'] = ReviewService::getInstance()->getBySalon(
                    $this->userId,
                    REVIEWS_PROFILE_COUNT
                );
            }
        } catch (PublicMessageException $e) {
            $profileContext['errorText'] = $e->getMessage();
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/ViewProfile.twig',
                array_merge($profileContext, $this->lkTwigContext)
            );
            Yii::app()->end();
        }

        //если профиль пустой, переходим на его редактирование
        if (empty($profileContext['companyName']) && empty($profileContext['lastName'])
            && empty($profileContext['firstName'])) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Profile.twig',
                array_merge($profileContext, $this->lkTwigContext)
            );
            Yii::app()->end();
        }

        echo (new TemplateEngineTwig)->render(
            'lk/' . $this->userType . '/ViewProfile.twig',
            array_merge($profileContext, $this->lkTwigContext)
        );
    }


    /**
     * Профиль другого пользователя (просмотр)
     */
    public function actionOtherProfile($userId)
    {
        $profileContext = [
            'viewInProfile' => VIEW_IN_PROFILE,
            /*для Twig компонента addressProfile*/
            'yandexGeoKey' => YANDEX_GEOCODER_KEY,
        ];

        try {
            $userModel = UserModel::model()->with('userType')->findByPk($userId);
            if (empty($userModel)) {
                throw new PublicMessageException('Пользователь не найден.');
            }
            $userType = $userModel->userType->name;

            $profileContext = array_merge(
                $profileContext,
                ProfileService::getInstance()->getProfile($userId, $userType, 'rand()')
            );

            if ($this->userType === UserTypeEnum::CLIENT) {
                $profileContext['reviews'] = ReviewService::getInstance()->getByUser($userId, REVIEWS_PROFILE_COUNT);
            } elseif ($this->userType === UserTypeEnum::MASTER) {
                $profileContext['reviews'] = ReviewService::getInstance()->getByMaster($userId, REVIEWS_PROFILE_COUNT);
                $profileContext['workPhotos'] = PortfolioService::getInstance()->getPortfolio(
                    $this->userId,
                    $this->userType
                );
            } else {
                $profileContext['reviews'] = ReviewService::getInstance()->randReview(REVIEWS_PROFILE_COUNT);
            }

            $profileContext['uId'] = $userModel->id;
            $profileContext['uType'] = $userType;
            $profileContext['uLogin'] = $userModel->login;
        } catch (PublicMessageException $e) {
            $profileContext['errorText'] = $e->getMessage();
            echo (new TemplateEngineTwig)->render(
                'lk/' . $userType . '/OtherProfile.twig',
                array_merge($profileContext, $this->lkTwigContext)
            );
            Yii::app()->end();
        }

        echo (new TemplateEngineTwig)->render(
            'lk/' . $userType . '/OtherProfile.twig',
            array_merge($profileContext, $this->lkTwigContext)
        );
    }

    /**
     * Профиль пользователя (редактирование)
     */
    public function actionProfile()
    {
        try {
            $profileContext = [
                /*для Twig компонента addressProfile*/
                'yandexGeoKey' => YANDEX_GEOCODER_KEY,
            ];

            $profileContext = array_merge($profileContext,
                ProfileService::getInstance()->getProfile($this->userId, $this->userType));
        } catch (PublicMessageException $e) {
            $profileContext['errorText'] = $e->getMessage();
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Profile.twig',
                array_merge($profileContext, $this->lkTwigContext)
            );
            Yii::app()->end();
        }

        echo (new TemplateEngineTwig)->render(
            'lk/' . $this->userType . '/Profile.twig',
            array_merge($profileContext, $this->lkTwigContext)
        );
    }

    /**
     * Профиль пользователя (редактирование) для админов
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userPhone телефон пользователя
     */
    public function actionProfileByAdmin($userId, $userPhone = null)
    {
        $profileContext = [
            'byAdmin' => true, //для администраторов

            /*для Twig компонента addressProfile*/
            'yandexGeoKey' => YANDEX_GEOCODER_KEY,
        ];

        try {
            if (UserService::getInstance()->checkAccessByAdmin($this->userId) === false) {
                throw new PublicMessageException(ErrorEnum::ACCESS_DENIED);
            }

            $userModel = UserService::getInstance()->getUserByIdOrPhone(
                $userId,
                (new PhoneHelper)->formatPhoneForSave($userPhone)
            );
            $profileContext['uId'] = $userModel->id;
            $profileContext['uType'] = $userModel->userType->name;
            $profileContext['uLogin'] = $userModel->login;

            $profileContext = array_merge($profileContext,
                ProfileService::getInstance()->getProfile($userModel->id, $userModel->userType->name));

            echo (new TemplateEngineTwig)->render(
                'lk/' . $userModel->userType->name . '/Profile.twig',
                array_merge($profileContext, $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            if (empty($userModel->userType->name)
                || $e->getMessage() === ErrorEnum::USER_NOT_FOUND
                || $e->getMessage() === ErrorEnum::ACCESS_DENIED) {
                echo (new TemplateEngineTwig)->render(
                    'lk/Error.twig',
                    array_merge([
                        'errorText' => $e->getMessage(),
                    ], $this->lkTwigContext)
                );
                Yii::app()->end();
            }

            $profileContext['errorText'] = $e->getMessage();
            echo (new TemplateEngineTwig)->render(
                'lk/' . $userModel->userType->name . '/Profile.twig',
                array_merge($profileContext, $this->lkTwigContext)
            );
            Yii::app()->end();
        }
    }

    /**
     * Подписка (оповещение) пользователя
     */
    public function actionNotification()
    {
        try {
            $notifySettings = NotificationService::getInstance()->getSettings($this->userId);
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Notification.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'defaultRadius' => MASTER_RADIUS_FROM_CLIENT / 1000,
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }

        echo (new TemplateEngineTwig)->render(
            'lk/' . $this->userType . '/Notification.twig',
            array_merge([
                'notifyTypeRadius' => (!empty($notifySettings['type']) && $notifySettings['type'] === NotifyTypeEnum::BY_RADIUS ? 'selected' : ''),
                'notifyTypeMetro' => (!empty($notifySettings['type']) && $notifySettings['type'] === NotifyTypeEnum::BY_NEAR_SUBWAY ? 'selected' : ''),
                'defaultRadius' => (!empty($notifySettings['radius']) ? $notifySettings['radius'] : MASTER_RADIUS_FROM_CLIENT / 1000),
            ], $this->lkTwigContext)
        );
    }

    /**
     * Пополнение баланса
     */
    public function actionRoboPay()
    {
        try {
            $invoiceId = $this->userId . INVOICE_DELIMITER . rand(100, 999);
            if (ROBO_TEST === 1) {
                $signatureValue = md5(ROBO_LOGIN . '::' . $invoiceId . ':' . ROBO_PASS_TEST_1);
            } else {
                $signatureValue = md5(ROBO_LOGIN . '::' . $invoiceId . ':' . ROBO_PASS_1);
            }

            echo (new TemplateEngineTwig)->render(
                'lk/RobokassaPay.twig',
                array_merge([
                    'balance' => RoboPayService::getInstance()->getBalance($this->userId),
                    'roboLogin' => ROBO_LOGIN,
                    'invoiceId' => $invoiceId,
                    'isTest' => ROBO_TEST,
                    'desc' => 'Пополнение баланса',
                    'defaultSum' => ROBO_DEFAULT_SUM,
                    'signatureValue' => $signatureValue,
                    'roboUrl' => 'https://auth.robokassa.ru/Merchant/PaymentForm/FormFLS.js',
                ], $this->lkTwigContext)
            );

        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/Error.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }


    }

    /**
     * Отзывы пользователя
     */
    public function actionReviews()
    {
        try {
            if ($this->userType === UserTypeEnum::CLIENT) {
                $reviews = ReviewService::getInstance()->getByUser($this->userId, 100);
            } elseif ($this->userType === UserTypeEnum::MASTER) {
                $reviews = ReviewService::getInstance()->getByMaster($this->userId, 100);
            } else {
                $reviews = ReviewService::getInstance()->randReview(SEARCH_RAND_COUNT);
            }
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Reviews.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }

        echo (new TemplateEngineTwig)->render(
            'lk/' . $this->userType . '/Reviews.twig',
            array_merge([
                'reviews' => $reviews,
            ], $this->lkTwigContext)
        );
    }


    /**
     * Сообщения пользователей, с которыми был контакт у данного пользователя
     */
    public function actionMessages()
    {
        try {
            $users = MessageService::getInstance()->getUsersByUser($this->userId);

        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Messages.twig',
                array_merge([
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                    'errorText' => $e->getMessage(),
                    'method' => __METHOD__,
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }

        echo (new TemplateEngineTwig)->render(
            'lk/' . $this->userType . '/Messages.twig',
            array_merge([
                'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                'users' => $users,
            ], $this->lkTwigContext)
        );
    }


    /**
     * Сообщения пользователей - написал сам пользователь $this->userId пользователю $toUserId и написал пользователю $this->userId пользователь $toUserId
     *
     * @param integer $toUserId идентификатор пользователя-получателя
     */
    public function actionMessagesByUser($toUserId)
    {
        try {
            $messages = MessageService::getInstance()->getByFromToUser($this->userId, $toUserId);

        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/MessagesByUser.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }

        echo (new TemplateEngineTwig)->render(
            'lk/' . $this->userType . '/MessagesByUser.twig',
            array_merge([
                'message' => current($messages),
                'messages' => $messages,
            ], $this->lkTwigContext)
        );
    }


    /**
     * Акции мастеров и салонов
     */
    public function actionPromotions()
    {
        try {
            $clientModel = ProfileService::getInstance()->getProfileModel(null, $this->userType, $this->userId);
            if (empty($clientModel)) {
                throw new PublicMessageException('Заполните профиль.');
            }
            $promotions = PromotionService::getInstance()->randReview(
                SEARCH_RAND_COUNT,
                $clientModel->latitude,
                $clientModel->longitude
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Promotions.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }

        echo (new TemplateEngineTwig)->render(
            'lk/' . $this->userType . '/Promotions.twig',
            array_merge([
                'promotions' => $promotions,
                'imgPhotoWidth' => IMG_BIG_WIDTH,
            ], $this->lkTwigContext)
        );
    }

    /**
     * Акции пользователя
     */
    public function actionMyPromotions()
    {
        try {
            $promotions = PromotionService::getInstance()->getByUser($this->userId);
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Promotions.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }

        echo (new TemplateEngineTwig)->render(
            'lk/' . $this->userType . '/Promotions.twig',
            array_merge([
                'promotions' => $promotions,
                'imgPhotoWidth' => 450,
            ], $this->lkTwigContext)
        );
    }

    /**
     * Время работы
     */
    public function actionSchedule()
    {
        $scheduleContext = [];
        try {
            $schedules = ScheduleService::getInstance()->get($this->userId);
            if (!empty($schedules)) {
                $scheduleContext = array_shift($schedules);
            }

            $phone = ProfileService::getInstance()->getPhone($this->userId, $this->userType);
        } catch (PublicMessageException $e) {
            $scheduleContext['errorText'] = $e->getMessage();
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Schedule.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }

        echo (new TemplateEngineTwig)->render(
            'lk/' . $this->userType . '/Schedule.twig',
            array_merge(
                ['phone' => $phone],
                array_merge($scheduleContext, $this->lkTwigContext)
            )
        );
    }


    /**
     * Стоимость и длительность услуг
     */
    public function actionServiceCost()
    {
        $directionsAndServices = [
            /*для Twig компонента addressProfile*/
            'yandexGeoKey' => YANDEX_GEOCODER_KEY,
        ];

        try {
            $profileModel = ProfileService::getInstance()->getProfileModel(null, $this->userType, $this->userId);
            if ($profileModel !== null) {
                $directionsAndServices = RubricatorService::getInstance()->getSelectedDirectionsAndServices(
                    $this->userType,
                    $profileModel->id,
                    [
                        'profileDirections' => RubricatorService::getInstance()->getDirections(),
                        'profileServices' => RubricatorService::getInstance()->getServices(),
                    ]
                );

                $directionsAndServices['selServicesExists'] = false;
                foreach ($directionsAndServices['profileServices'] as $pService) {
                    if ($pService['selected'] === 'selected') {
                        $directionsAndServices['selServicesExists'] = true;
                        break;
                    }
                }
            }

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/ServiceCost.twig',
                array_merge($directionsAndServices, $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            $directionsAndServices['errorText'] = $e->getMessage();
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Profile.twig',
                array_merge($directionsAndServices, $this->lkTwigContext)
            );
            Yii::app()->end();
        }
    }


    /**
     * Стоимость и длительность услуг пользователя для редактирования админом
     */
    public function actionServiceCostByAdmin($userId)
    {
        $serviceCostContext = [
            'byAdmin' => true, //для администраторов
        ];

        try {
            if (UserService::getInstance()->checkAccessByAdmin($this->userId) === false) {
                throw new PublicMessageException(ErrorEnum::ACCESS_DENIED);
            }

            $userModel = UserModel::model()->with('userType')->findByPk($userId);
            $profileModel = ProfileService::getInstance()->getProfileModel(
                null,
                $userModel->userType->name,
                $userModel->id
            );
            if ($profileModel !== null) {
                $serviceCostContext = array_merge(RubricatorService::getInstance()->getSelectedDirectionsAndServices(
                    $userModel->userType->name,
                    $profileModel->id,
                    [
                        'profileDirections' => RubricatorService::getInstance()->getDirections(),
                        'profileServices' => RubricatorService::getInstance()->getServices(),
                    ]
                ), $serviceCostContext);

                $serviceCostContext['selServicesExists'] = false;
                foreach ($serviceCostContext['profileServices'] as $pService) {
                    if ($pService['selected'] === 'selected') {
                        $serviceCostContext['selServicesExists'] = true;
                        break;
                    }
                }
            }

            $serviceCostContext['uId'] = $userModel->id;
            $serviceCostContext['uType'] = $userModel->userType->name;
            $serviceCostContext['uLogin'] = $userModel->login;
            echo (new TemplateEngineTwig)->render(
                'lk/' . $userModel->userType->name . '/ServiceCost.twig',
                array_merge($serviceCostContext, $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            if ($e->getMessage() === ErrorEnum::NOT_FOUND_FOR_USER_TYPE
                || $e->getMessage() === ErrorEnum::USER_NOT_FOUND
                || $e->getMessage() === ErrorEnum::ACCESS_DENIED) {
                echo (new TemplateEngineTwig)->render(
                    'lk/Error.twig',
                    array_merge([
                        'errorText' => $e->getMessage(),
                    ], $this->lkTwigContext)
                );
                Yii::app()->end();
            }

            $serviceCostContext['errorText'] = $e->getMessage();
            echo (new TemplateEngineTwig)->render(
                'lk/' . $userModel->userType->name . '/ServiceCost.twig',
                array_merge($serviceCostContext, $this->lkTwigContext)
            );
            Yii::app()->end();
        }
    }


    /**
     * Заказы клиентов (только созданные), либо заказы сделанные клиентами салона напрямую через этот сайт
     */
    public function actionClientOrders()
    {
        $createdOrders = [];
        $notAgreeOrders = [];
        $agreeOrders = [];
        $doneOrders = [];
        $assessmentOrders = [];

        try {
            if ($this->userType === UserTypeEnum::MASTER) {
                $createdOrders = OrderService::getInstance()->getCreatedOrdersNearBy($this->userId, $this->userType);
            } elseif ($this->userType === UserTypeEnum::SALON) {
                $createdOrders = OrderService::getInstance()->getCreatedOrdersNearBy($this->userId, $this->userType);
                $notAgreeOrders = OrderService::getInstance()->getNotAgreeOrdersBySalon(
                    $this->userId,
                    $this->userType
                );
                $orderBySalon = OrderService::getInstance()->getOrdersBySalon($this->userId, $this->userType);
                foreach ($orderBySalon as $order) {
                    if ($order['status'] === OrderStatusEnum::AGREE) {
                        $agreeOrders[] = $order;
                    }
                    if ($order['status'] === OrderStatusEnum::DONE || $order['status'] === OrderStatusEnum::ASSESSMENT) {
                        $doneOrders[] = $order;
                    }
                }
            } else {
                throw new PublicMessageException(ErrorEnum::TEMPLATE_NOT_EXIST_FOR_USER_TYPE);
            }

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/ClientOrders.twig',
                array_merge([
                    'createdOrders' => $createdOrders,
                    'notAgreeOrders' => $notAgreeOrders,
                    'agreeOrders' => $agreeOrders,
                    'doneOrders' => $doneOrders,
                    'assessmentOrders' => $assessmentOrders,
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            if ($e->getMessage() === ErrorEnum::TEMPLATE_NOT_EXIST_FOR_USER_TYPE) {
                echo (new TemplateEngineTwig)->render(
                    'lk/Error.twig',
                    array_merge([
                        'errorText' => $e->getMessage(),
                    ], $this->lkTwigContext)
                );
            }

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/ClientOrders.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }
    }


    /**
     * Мои заказы, либо заказы сделанные клиентами салона через оператора под учеткой салона
     */
    public function actionMyOrders()
    {
        try {
            $myOrders = [];
            $createdOrders = [];
            $notAgreeOrders = [];
            $agreeOrders = [];
            $doneOrders = [];
            $assessmentOrders = [];

            if ($this->userType === UserTypeEnum::CLIENT || $this->userType === UserTypeEnum::SALON) {
                $myOrders = OrderService::getInstance()->getMyOrders($this->userId, $this->userType);
                foreach ($myOrders as $order) {
                    if ($order['status'] === OrderStatusEnum::CREATED) {
                        $createdOrders[] = $order;
                    }
                    if ($order['status'] === OrderStatusEnum::SEND_TO_AGREE) {
                        $notAgreeOrders[] = $order;
                    }
                    if ($order['status'] === OrderStatusEnum::AGREE) {
                        $agreeOrders[] = $order;
                    }
                    if ($order['status'] === OrderStatusEnum::DONE || $order['status'] === OrderStatusEnum::ASSESSMENT) {
                        $doneOrders[] = $order;
                    }
                }
            } elseif ($this->userType === UserTypeEnum::MASTER) {
                $notAgreeOrders = OrderService::getInstance()->getNotAgreeOrdersByMaster(
                    $this->userId,
                    $this->userType
                );

                $orderByMaster = OrderService::getInstance()->getOrdersByMaster($this->userId, $this->userType);
                foreach ($orderByMaster as $order) {
                    if ($order['status'] === OrderStatusEnum::AGREE) {
                        $agreeOrders[] = $order;
                    }
                    if ($order['status'] === OrderStatusEnum::DONE || $order['status'] === OrderStatusEnum::ASSESSMENT) {
                        $doneOrders[] = $order;
                    }
                }
            } else {
                throw new PublicMessageException(ErrorEnum::TEMPLATE_NOT_EXIST_FOR_USER_TYPE);
            }

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/MyOrders.twig',
                array_merge([
                    'myOrders' => $myOrders,
                    'createdOrders' => $createdOrders,
                    'notAgreeOrders' => $notAgreeOrders,
                    'agreeOrders' => $agreeOrders,
                    'doneOrders' => $doneOrders,
                    'assessmentOrders' => $assessmentOrders,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            if ($e->getMessage() === ErrorEnum::TEMPLATE_NOT_EXIST_FOR_USER_TYPE) {
                echo (new TemplateEngineTwig)->render(
                    'lk/Error.twig',
                    array_merge([
                        'errorText' => $e->getMessage(),
                    ], $this->lkTwigContext)
                );
            }

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/MyOrders.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }
    }

    /**
     * Предложения от клиентов
     */
    public function actionNotAgreeOrders()
    {
        try {
            $notAgreeOrders = OrderService::getInstance()->getNotAgreeOrdersByMaster($this->userId, $this->userType);

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/ClientOrders.twig',
                array_merge([
                    'allOrders' => $notAgreeOrders,
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/ClientOrders.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }
    }

    /**
     * Вакансии салона
     *
     * @param integer $salonId идентификатор профиля выбранного в поиске салона
     */
    public function actionVacancies($salonId = null)
    {
        try {
            if ($this->userType === UserTypeEnum::SALON) {
                $vacancies = VacancyService::getInstance()->getMyVacancies($this->userId);
            } elseif ($this->userType === UserTypeEnum::MASTER) {
                $profileModel = ProfileService::getInstance()->getProfileModel(null, $this->userType, $this->userId);
                $vacancies = VacancyService::getInstance()->getAllVacanciesByDirection(
                    RubricatorService::getInstance()->getSelectedDirections(
                        $this->userType,
                        $profileModel->id
                    )
                );
            } else {
                throw new PublicMessageException(ErrorEnum::TEMPLATE_NOT_EXIST_FOR_USER_TYPE);
            }

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Vacancies.twig',
                array_merge([
                    'vacancies' => $vacancies,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Vacancies.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }
    }

    /**
     * Отображение заказа клиента по id
     *
     * @param integer $id номер заказа
     */
    public function actionOrder($id)
    {
        $templateName = 'lk/' . $this->userType . '/Order.twig';

        $masterModel = null;
        $masters = [];
        $notAgreeMasters = [];
        $notAgreeOwnMasters = [];
        $isAgreeClientThisMasterOffer = false;

        $salonModel = null;
        $salons = [];
        $notAgreeSalons = [];
        $notAgreeOwnSalons = [];
        $isAgreeClientThisSalonOffer = false;
        $reviews = [];

        try {
            $orderModel = OrderService::getInstance()->getById($this->userId, $this->userType, $id);

            if ($orderModel === null) {
                throw new PublicMessageException('Заказ не найден.');
            }

            if ($this->userType === UserTypeEnum::CLIENT) {
                $clientModel = ProfileService::getInstance()->getProfileModel(null, $this->userType, $this->userId);
                if (!empty($clientModel)) {
                    if (IntVal($orderModel->status) === OrderStatusEnum::CREATED) {
                        $masters = SearchService::getInstance()->findMasterByOrder($clientModel, $orderModel);
                        $salons = SearchService::getInstance()->findSalonByOrder($clientModel, $orderModel);
                    } elseif (IntVal($orderModel->status) === OrderStatusEnum::SEND_TO_AGREE) {
                        $notAgreeMasters = OrderService::getInstance()->findBufMasterByOrder($clientModel, $orderModel);
                        $notAgreeOwnMasters = OrderService::getInstance()->findBufOwnMasterByOrder($clientModel,
                            $orderModel);

                        $notAgreeSalons = OrderService::getInstance()->findBufSalonByOrder($clientModel, $orderModel);
                        $notAgreeOwnSalons = OrderService::getInstance()->findBufOwnSalonByOrder(
                            $clientModel,
                            $orderModel
                        );
                    } elseif (IntVal($orderModel->status) === OrderStatusEnum::ASSESSMENT) {
                        $reviews = ReviewService::getInstance()->getByOrder(
                            $orderModel->id
                        );
                    }
                }
                //для мастеров
            } elseif ($this->userType === UserTypeEnum::MASTER) {
                $masterModel = MasterProfileModel::model()->findByAttributes(['user_id' => $this->userId]);

                if (IntVal($orderModel->status) === OrderStatusEnum::SEND_TO_AGREE) {
                    //проверяем был ли отклик на заказ от данного мастера
                    $masterBufModel = BufMasterOrderModel::model()->findByPk([
                        'profile_id' => $masterModel->id,
                        'order_id' => $orderModel->id,
                    ]);
                    if (!empty($masterBufModel)) {
                        //если был, то смотрим согласился ли клиент
                        $isAgreeClientThisMasterOffer = ((new BoolFormatter)->format($masterBufModel->client_agree) === true);
                    }
                } elseif (IntVal($orderModel->status) === OrderStatusEnum::ASSESSMENT) {
                    $reviews = ReviewService::getInstance()->getByOrder(
                        $orderModel->id
                    );
                }
                //для салонов
            } elseif ($this->userType === UserTypeEnum::SALON) {
                $salonModel = SalonProfileModel::model()->findByAttributes(['user_id' => $this->userId]);
                if (empty($salonModel)) {
                    throw new PublicMessageException('Заполните профиль салона.');
                }

                //если заказ сделан непосредственно клиентом
                if ($orderModel->userType->name === UserTypeEnum::CLIENT) {
                    if (IntVal($orderModel->status) === OrderStatusEnum::SEND_TO_AGREE) {
                        //проверяем был ли отклик на заказ от данного салона
                        $salonBufModel = BufSalonOrderModel::model()->findByPk([
                            'profile_id' => $salonModel->id,
                            'order_id' => $orderModel->id,
                        ]);
                        if (!empty($salonBufModel)) {
                            //если был, то смотрим согласился ли клиент
                            $isAgreeClientThisSalonOffer = ((new BoolFormatter)->format($salonBufModel->client_agree) === true);
                        }
                    }
                    //если заказ сделан оператором салона для клиента
                } elseif ($orderModel->userType->name === UserTypeEnum::SALON) {
                    $templateName = 'lk/' . $this->userType . '/ClientOfSalonOrder.twig';

                    if (IntVal($orderModel->status) === OrderStatusEnum::CREATED) {
                        $masters = SearchService::getInstance()->findMasterByOrder($salonModel, $orderModel);
                    } elseif (IntVal($orderModel->status) === OrderStatusEnum::SEND_TO_AGREE) {
                        $notAgreeMasters = OrderService::getInstance()->findBufMasterByOrder($salonModel,
                            $orderModel);
                        $notAgreeOwnMasters = OrderService::getInstance()->findBufOwnMasterByOrder(
                            $salonModel,
                            $orderModel
                        );
                    }
                } else {
                    throw new PublicMessageException('Неправильный тип создателя заказа.');
                }

                if (IntVal($orderModel->status) === OrderStatusEnum::ASSESSMENT) {
                    $reviews = ReviewService::getInstance()->getByOrder(
                        $orderModel->id
                    );
                }

            } else {
                throw new PublicMessageException(ErrorEnum::TEMPLATE_NOT_EXIST_FOR_USER_TYPE);
            }

            echo (new TemplateEngineTwig)->render(
                $templateName,
                array_merge([
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                    'orderModel' => $orderModel,
                    'rusPlace' => $orderModel->getRusPlace(),
                    'reviews' => $reviews,

                    'masterProfileModel' => $masterModel,
                    'isAgreeClientThisMasterOffer' => $isAgreeClientThisMasterOffer,
                    'masters' => $masters,
                    'notAgreeMasters' => $notAgreeMasters,
                    'notAgreeOwnMasters' => $notAgreeOwnMasters,

                    'salonProfileModel' => $salonModel,
                    'isAgreeClientThisSalonOffer' => $isAgreeClientThisSalonOffer,
                    'salons' => $salons,
                    'notAgreeSalons' => $notAgreeSalons,
                    'notAgreeOwnSalons' => $notAgreeOwnSalons,

                    'orderStatusCreated' => OrderStatusEnum::CREATED,
                    'orderStatusNotAgree' => OrderStatusEnum::SEND_TO_AGREE,
                    'orderStatusAgree' => OrderStatusEnum::AGREE,
                    'orderStatusDone' => OrderStatusEnum::DONE,
                    'orderStatusAssessment' => OrderStatusEnum::ASSESSMENT,
                    'orderStatusDelete' => OrderStatusEnum::DELETED,

                    'orderPhotoUrl' => !empty($orderModel->photo_file_name) ? IMG_ORDER_DIR . $orderModel->photo_file_name : '',
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            if ($e->getMessage() === ErrorEnum::TEMPLATE_NOT_EXIST_FOR_USER_TYPE) {
                echo (new TemplateEngineTwig)->render(
                    'lk/Error.twig',
                    array_merge([
                        'errorText' => $e->getMessage(),
                    ], $this->lkTwigContext)
                );
            }

            echo (new TemplateEngineTwig)->render(
                $templateName,
                array_merge([
                    'errorText' => $e->getMessage(),
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,

                    'orderStatusCreated' => OrderStatusEnum::CREATED,
                    'orderStatusNotAgree' => OrderStatusEnum::SEND_TO_AGREE,
                    'orderStatusAgree' => OrderStatusEnum::AGREE,
                    'orderStatusDone' => OrderStatusEnum::DONE,
                    'orderStatusDelete' => OrderStatusEnum::DELETED,
                ], $this->lkTwigContext)
            );
            Yii::app()->end();
        }
    }

    //====================================================================================================

    /**
     * Отправка сообщений через Pusher
     */
    public function actionChatSendMess()
    {
        try {
            UserService::getInstance()->chatSendMess(
                Yii::app()->request->getParam('message')
            );
        } catch (PublicMessageException $e) {
            $this->response('Ошибка отправки: ' . $e->getMessage(), true);
            Yii::app()->end();
        }

        $this->response('Ok', false);
    }


    /**
     * Сохранение профиля пользователя
     */
    public function actionSaveProfile()
    {
        $contextForm = [
            'firstName' => Yii::app()->request->getParam('profile-first-name'),
            'lastName' => Yii::app()->request->getParam('profile-last-name'),
            'middleName' => Yii::app()->request->getParam('profile-middle-name'),
            'address' => Yii::app()->request->getParam('profile-address'),
            'addressCoord' => Yii::app()->request->getParam('profile-address-coord'),
            'workExperience' => Yii::app()->request->getParam('profile-work-experience'),
            'about' => Yii::app()->request->getParam('profile-about'),
            'youtubeUrl' => Yii::app()->request->getParam('profile-video-link'),
            'selectedDirections' => Yii::app()->request->getParam('profile-directions'),
            'selectedServices' => Yii::app()->request->getParam('profile-services'),
            'profilePlace' => Yii::app()->request->getParam('profile-place'),
            'phone' => Yii::app()->request->getParam('profile-phone'),
            'smsConfirm' => Yii::app()->request->getParam('sms-confirm'),
        ];

        if ($this->userType === UserTypeEnum::MASTER) {
            $contextForm['is_vacancy'] = Yii::app()->request->getParam('profile-is-vacancy');
        }

        if ($this->userType === UserTypeEnum::SALON) {
            $contextForm['inn'] = Yii::app()->request->getParam('profile-inn');
            $contextForm['companyName'] = Yii::app()->request->getParam('profile-company-name');
            $contextForm['urName'] = Yii::app()->request->getParam('profile-ur-name');
        }

        try {
            ProfileService::getInstance()->save(
                $this->userId,
                $this->userType,
                array_merge([
                    'photo' => isset($_FILES['profile-user-photo']) ? $_FILES['profile-user-photo'] : null,
                ], $contextForm)
            );

            if ($this->userType === UserTypeEnum::CLIENT) {
                Yii::app()->request->redirect('/lk/viewProfile');
            } elseif ($this->userType === UserTypeEnum::MASTER) {
                Yii::app()->request->redirect('/lk/notification');
            } elseif ($this->userType === UserTypeEnum::SALON) {
                Yii::app()->request->redirect('/lk/schedule');
            }
        } catch (PublicMessageException $e) {
            try {
                $profileDirections = RubricatorService::getInstance()->getDirections();
                $profileServices = RubricatorService::getInstance()->getServices();
            } catch (PublicMessageException $e2) {
            }

            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/Profile.twig',
                array_merge([
                    'errorText' => $e->getMessage(),

                    /*для Twig компонента addressProfile*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'yandexGeoZoom' => isset($contextForm['addressCoord']) ? YANDEX_GEOCODER_ADDRESS_ZOOM : YANDEX_GEOCODER_ZOOM,
                    'yandexGeoCoord' => isset($contextForm['addressCoord']) ? $contextForm['addressCoord'] : YANDEX_GEOCODER_COORD,

                    'profileDirections' => $profileDirections,
                    'profileServices' => $profileServices,
                ], $contextForm));
            Yii::app()->end();
        }
    }

    /**
     * Сохранение профиля пользователя
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     */
    public function actionSaveProfileByAdmin($userId, $userType)
    {

        $contextForm = [
            'firstName' => Yii::app()->request->getParam('profile-first-name'),
            'lastName' => Yii::app()->request->getParam('profile-last-name'),
            'middleName' => Yii::app()->request->getParam('profile-middle-name'),
            'address' => Yii::app()->request->getParam('profile-address'),
            'addressCoord' => Yii::app()->request->getParam('profile-address-coord'),
            'workExperience' => Yii::app()->request->getParam('profile-work-experience'),
            'about' => Yii::app()->request->getParam('profile-about'),
            'youtubeUrl' => Yii::app()->request->getParam('profile-video-link'),
            'selectedDirections' => Yii::app()->request->getParam('profile-directions'),
            'selectedServices' => Yii::app()->request->getParam('profile-services'),
            'profilePlace' => Yii::app()->request->getParam('profile-place'),
            'phone' => Yii::app()->request->getParam('profile-phone'),
            'smsConfirm' => Yii::app()->request->getParam('sms-confirm'),
        ];

        if ($userType === UserTypeEnum::MASTER) {
            $contextForm['is_vacancy'] = Yii::app()->request->getParam('profile-is-vacancy');
        }

        if ($userType === UserTypeEnum::SALON) {
            $contextForm['inn'] = Yii::app()->request->getParam('profile-inn');
            $contextForm['companyName'] = Yii::app()->request->getParam('profile-company-name');
            $contextForm['urName'] = Yii::app()->request->getParam('profile-ur-name');
        }

        try {
            if (UserService::getInstance()->checkAccessByAdmin($this->userId) === false) {
                throw new PublicMessageException(ErrorEnum::ACCESS_DENIED);
            }

            ProfileService::getInstance()->save(
                $userId,
                $userType,
                array_merge([
                    'photo' => isset($_FILES['profile-user-photo']) ? $_FILES['profile-user-photo'] : null,
                ], $contextForm)
            );

            Yii::app()->request->redirect('/lk/ProfileByAdmin?userId=' . IntVal($userId));

        } catch (PublicMessageException $e) {
            if ($e->getMessage() === ErrorEnum::USER_NOT_FOUND || $e->getMessage() === ErrorEnum::ACCESS_DENIED) {
                echo (new TemplateEngineTwig)->render(
                    'lk/Error.twig',
                    array_merge([
                        'errorText' => $e->getMessage(),
                    ], $this->lkTwigContext)
                );
                Yii::app()->end();
            }

            try {
                $profileDirections = RubricatorService::getInstance()->getDirections();
                $profileServices = RubricatorService::getInstance()->getServices();
            } catch (PublicMessageException $e2) {
            }

            echo (new TemplateEngineTwig)->render('lk/' . $userType . '/Profile.twig',
                array_merge([
                    'errorText' => $e->getMessage(),

                    /*для Twig компонента addressProfile*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'yandexGeoZoom' => isset($contextForm['addressCoord']) ? YANDEX_GEOCODER_ADDRESS_ZOOM : YANDEX_GEOCODER_ZOOM,
                    'yandexGeoCoord' => isset($contextForm['addressCoord']) ? $contextForm['addressCoord'] : YANDEX_GEOCODER_COORD,

                    'profileDirections' => $profileDirections,
                    'profileServices' => $profileServices,
                ], $contextForm));
            Yii::app()->end();
        }
    }


    /**
     * Сохранение акции пользователя
     */
    public function actionSavePromotion()
    {
        $contextForm = [
            'dateBegin' => Yii::app()->request->getParam('promotion-date-begin'),
            'dateEnd' => Yii::app()->request->getParam('promotion-date-end'),
            'title' => Yii::app()->request->getParam('promotion-title'),
            'about' => Yii::app()->request->getParam('profile-about'),
            'discount' => Yii::app()->request->getParam('promotion-discount'),
            'address' => Yii::app()->request->getParam('profile-address'),
            'addressCoord' => Yii::app()->request->getParam('profile-address-coord'),
        ];

        try {
            PromotionService::getInstance()->save(
                $this->userId,
                array_merge([
                    'photo' => isset($_FILES['profile-user-photo']) ? $_FILES['profile-user-photo'] : null,
                ], $contextForm)
            );

            Yii::app()->request->redirect('/lk/myPromotions');
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/CreatePromotion.twig',
                array_merge([
                    'errorText' => $e->getMessage(),

                    'photoLabel' => 'Фото акции:<span class="text-danger">*</span>',
                    'aboutLabel' => 'Условия акции:<span class="text-danger">*</span>',

                    /*для Twig компонента photoProfile*/
                    'isRequiredUploadPhoto' => true,

                    /*для Twig компонента addressProfile*/
                    'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                    'yandexGeoZoom' => isset($contextForm['addressCoord']) ? YANDEX_GEOCODER_ADDRESS_ZOOM : YANDEX_GEOCODER_ZOOM,
                    'yandexGeoCoord' => isset($contextForm['addressCoord']) ? $contextForm['addressCoord'] : YANDEX_GEOCODER_COORD,
                ], $contextForm));
        }
    }


    /**
     * Сохранение фото работ мастера
     */
    public function actionSavePortfolio()
    {
        try {
            PortfolioService::getInstance()->savePortfolio(
                $this->userId,
                $this->userType, [
                'photo' => isset($_FILES['profile-user-photo']) ? $_FILES['profile-user-photo'] : null,
                'about' => Yii::app()->request->getParam('portfolio-about'),
            ]);
        } catch (PublicMessageException $e) {
            try {
                $workPhotos = PortfolioService::getInstance()->getPortfolio($this->userId, $this->userType);
            } catch (PublicMessageException $e) {
                echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/Portfolio.twig', [
                    'workPhotos' => [],
                    'errorText' => $e->getMessage(),

                    /*для Twig компонента photoProfile*/
                    'isRequiredUploadPhoto' => true,
                ]);
                Yii::app()->end();
            }

            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/Portfolio.twig', [
                'workPhotos' => $workPhotos,
                'errorText' => $e->getMessage(),

                /*для Twig компонента photoProfile*/
                'isRequiredUploadPhoto' => true,
            ]);
            Yii::app()->end();
        }

        try {
            $workPhotos = PortfolioService::getInstance()->getPortfolio($this->userId, $this->userType);
            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/Portfolio.twig',
                array_merge([
                    'workPhotos' => $workPhotos,

                    /*для Twig компонента photoProfile*/
                    'isRequiredUploadPhoto' => true,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/Portfolio.twig', [
                'workPhotos' => [],
                'errorText' => $e->getMessage(),

                /*для Twig компонента photoProfile*/
                'isRequiredUploadPhoto' => true,
            ]);
        }
    }


    /**
     * Сохранение видео обучения
     */
    public function actionSaveStudyVideo()
    {
        $contextForm = [
            'directionId' => Yii::app()->request->getParam('study-video-direction'),
            'title' => Yii::app()->request->getParam('study-video-title'),
            'link' => Yii::app()->request->getParam('study-video-link'),
            'description' => Yii::app()->request->getParam('study-video-description'),
        ];

        try {
            StudyService::getInstance()->saveVideo(
                $this->userId,
                $this->userType,
                $contextForm
            );

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/StudyVideo.twig',
                array_merge([
                    'videos' => StudyService::getInstance()->getVideos($this->userId),
                    'directions' => RubricatorService::getInstance()->getDirections(false),
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/StudyVideo.twig',
                array_merge($contextForm,
                    array_merge([
                        'videos' => StudyService::getInstance()->getVideos($this->userId),
                        'directions' => RubricatorService::getInstance()->getDirections(false),
                        'errorText' => $e->getMessage(),
                    ], $this->lkTwigContext))
            );
            Yii::app()->end();
        }
    }


    /**
     * Сохранение фото работ мастера
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     */
    public function actionSavePortfolioByAdmin($userId, $userType)
    {
        try {
            if (UserService::getInstance()->checkAccessByAdmin($this->userId) === false) {
                throw new PublicMessageException(ErrorEnum::ACCESS_DENIED);
            }

            PortfolioService::getInstance()->savePortfolio(
                $userId,
                $userType, [
                'photo' => isset($_FILES['profile-user-photo']) ? $_FILES['profile-user-photo'] : null,
                'about' => Yii::app()->request->getParam('portfolio-about'),
            ]);
        } catch (PublicMessageException $e) {
            if ($e->getMessage() === ErrorEnum::USER_NOT_FOUND || $e->getMessage() === ErrorEnum::ACCESS_DENIED) {
                echo (new TemplateEngineTwig)->render(
                    'lk/Error.twig',
                    array_merge([
                        'errorText' => $e->getMessage(),
                    ], $this->lkTwigContext)
                );
                Yii::app()->end();
            }

            try {
                $workPhotos = PortfolioService::getInstance()->getPortfolio($userId, $userType);
            } catch (PublicMessageException $e) {
                echo (new TemplateEngineTwig)->render('lk/' . $userType . '/Portfolio.twig', [
                    'workPhotos' => [],
                    'errorText' => $e->getMessage(),

                    /*для Twig компонента photoProfile*/
                    'isRequiredUploadPhoto' => true,
                ]);
                Yii::app()->end();
            }

            echo (new TemplateEngineTwig)->render('lk/' . $userType . '/Portfolio.twig', [
                'byAdmin' => true,
                'uId' => $userId,
                'uType' => $userType,
                'uLogin' => '№' . $userId,

                'workPhotos' => $workPhotos,
                'errorText' => $e->getMessage(),

                /*для Twig компонента photoProfile*/
                'isRequiredUploadPhoto' => true,
            ]);
            Yii::app()->end();
        }

        try {
            $workPhotos = PortfolioService::getInstance()->getPortfolio($userId, $userType);
            echo (new TemplateEngineTwig)->render('lk/' . $userType . '/Portfolio.twig',
                array_merge([
                    'workPhotos' => $workPhotos,
                    'byAdmin' => true,
                    'uId' => $userId,
                    'uType' => $userType,
                    'uLogin' => '№' . $userId,

                    /*для Twig компонента photoProfile*/
                    'isRequiredUploadPhoto' => true,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('lk/' . $userType . '/Portfolio.twig', [
                'workPhotos' => [],
                'errorText' => $e->getMessage(),

                /*для Twig компонента photoProfile*/
                'isRequiredUploadPhoto' => true,
            ]);
            Yii::app()->end();
        }
    }


    /**
     * Сохранение времени работы мастера (салона) и телефона
     */
    public function actionSaveScheduleAndPhone()
    {
        try {
            ScheduleService::getInstance()->save(
                $this->userId,
                $this->userType, [
                'scheduleId' => Yii::app()->request->getParam('schedule-id'),
                'weekdays' => Yii::app()->request->getParam('schedule-weekday'),
                'timeBegin' => Yii::app()->request->getParam('profile-timepicker-begin'),
                'timeEnd' => Yii::app()->request->getParam('profile-timepicker-end'),
            ]);

            ProfileService::getInstance()->savePhone(
                $this->userId,
                $this->userType,
                Yii::app()->request->getParam('profile-phone')
            );

            Yii::app()->request->redirect('/lk/serviceCost');
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Schedule.twig',
                array_merge([
                    'scheduleId' => Yii::app()->request->getParam('schedule-id'),
                    'weekdays' => ScheduleService::getInstance()->getWeekDaysBase(),
                    'timeBegin' => Yii::app()->request->getParam('profile-timepicker-begin'),
                    'timeEnd' => Yii::app()->request->getParam('profile-timepicker-end'),
                    'phone' => Yii::app()->request->getParam('profile-phone'),
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
        }
    }


    /**
     * Сохранение настроек подписки
     */
    public function actionSaveNotification()
    {
        try {
            NotificationService::getInstance()->saveSettings(
                $this->userId,
                Yii::app()->request->getParam('notify-type'),
                Yii::app()->request->getParam('radius')
            );

            Yii::app()->request->redirect('/lk/viewProfile');
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Notification.twig',
                array_merge([
                    'notifyTypeRadius' => (Yii::app()->request->getParam('notify-type') === NotifyTypeEnum::BY_RADIUS ? 'selected' : ''),
                    'notifyTypeMetro' => (Yii::app()->request->getParam('notify-type') === NotifyTypeEnum::BY_NEAR_SUBWAY ? 'selected' : ''),
                    'defaultRadius' => Yii::app()->request->getParam('radius'),
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
        }
    }

    /**
     * Сохранение стоимости и длительности услуг мастера
     */
    public function actionSaveServiceCost()
    {
        $selectedDuration = [];
        $selectedCost = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'service-duration-') === 0) {
                $serviceId = str_replace('service-duration-', '', $key);
                $selectedDuration[$serviceId] = $value;
            }
            if (strpos($key, 'service-cost-') === 0) {
                $serviceId = str_replace('service-cost-', '', $key);
                $selectedCost[$serviceId] = abs($value);
            }
        }

        try {
            RubricatorService::getInstance()->saveServiceCost(
                $this->userId,
                $this->userType,
                $selectedDuration,
                $selectedCost,
                RubricatorService::getInstance()->parsingAdditionServicePostFields($_POST)
            );

            Yii::app()->request->redirect('/lk/myOrders');
        } catch (PublicMessageException $e) {
            $directionsAndServices = [];
            try {
                $profileModel = ProfileService::getInstance()->getProfileModel(null, $this->userType, $this->userId);
                $directionsAndServices = RubricatorService::getInstance()->getSelectedDirectionsAndServices(
                    $this->userType,
                    $profileModel->id,
                    [
                        'profileDirections' => RubricatorService::getInstance()->getDirections(),
                        'profileServices' => RubricatorService::getInstance()->getServices(),
                    ]
                );
            } catch (PublicMessageException $e) {
                $directionsAndServices['errorText'] = $e->getMessage();
                echo (new TemplateEngineTwig)->render(
                    'lk/' . $this->userType . '/ServiceCost.twig',
                    array_merge($directionsAndServices, $this->lkTwigContext)
                );
            }

            $directionsAndServices['errorText'] = $e->getMessage();
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/ServiceCost.twig',
                array_merge($directionsAndServices, $this->lkTwigContext)
            );
        }
    }


    /**
     * Сохранение стоимости и длительности услуг мастера
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     */
    public function actionSaveServiceCostByAdmin($userId, $userType)
    {
        $selectedDuration = [];
        $selectedCost = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'service-duration-') === 0) {
                $serviceId = str_replace('service-duration-', '', $key);
                $selectedDuration[$serviceId] = $value;
            }
            if (strpos($key, 'service-cost-') === 0) {
                $serviceId = str_replace('service-cost-', '', $key);
                $selectedCost[$serviceId] = abs($value);
            }
        }

        try {
            if (UserService::getInstance()->checkAccessByAdmin($this->userId) === false) {
                throw new PublicMessageException(ErrorEnum::ACCESS_DENIED);
            }

            RubricatorService::getInstance()->saveServiceCost(
                $userId,
                $userType,
                $selectedDuration,
                $selectedCost,
                RubricatorService::getInstance()->parsingAdditionServicePostFields($_POST)
            );

            Yii::app()->request->redirect('/lk/serviceCostByAdmin?userId=' . IntVal($userId));
        } catch (PublicMessageException $e) {
            if ($e->getMessage() === ErrorEnum::USER_NOT_FOUND || $e->getMessage() === ErrorEnum::ACCESS_DENIED) {
                echo (new TemplateEngineTwig)->render(
                    'lk/Error.twig',
                    array_merge([
                        'errorText' => $e->getMessage(),
                    ], $this->lkTwigContext)
                );
                Yii::app()->end();
            }

            $directionsAndServices = [];
            try {
                $profileModel = ProfileService::getInstance()->getProfileModel(null, $userType, $userId);
                $directionsAndServices = RubricatorService::getInstance()->getSelectedDirectionsAndServices(
                    $userType,
                    $profileModel->id,
                    [
                        'profileDirections' => RubricatorService::getInstance()->getDirections(),
                        'profileServices' => RubricatorService::getInstance()->getServices(),
                    ]
                );
            } catch (PublicMessageException $e) {
                $directionsAndServices['errorText'] = $e->getMessage();
                echo (new TemplateEngineTwig)->render(
                    'lk/' . $userType . '/ServiceCost.twig',
                    array_merge($directionsAndServices, $this->lkTwigContext)
                );
            }

            $directionsAndServices['errorText'] = $e->getMessage();
            echo (new TemplateEngineTwig)->render(
                'lk/' . $userType . '/ServiceCost.twig',
                array_merge($directionsAndServices, $this->lkTwigContext)
            );
        }
    }

    /**
     * Создание вакансии
     */
    public function actionCreateVacancy()
    {
        try {
            VacancyService::getInstance()->create(
                $this->userId,
                Yii::app()->request->getParam('vacancy-profession'),
                Yii::app()->request->getParam('vacancy-work-experience'),
                Yii::app()->request->getParam('vacancy-weekday'),
                Yii::app()->request->getParam('profile-timepicker-begin'),
                Yii::app()->request->getParam('profile-timepicker-end'),
                Yii::app()->request->getParam('vacancy-about'),
                Yii::app()->request->getParam('vacancy-salary'),
                Yii::app()->request->getParam('vacancy-phone')
            );

            Yii::app()->request->redirect('/lk/vacancies');
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/createVacancy.twig',
                array_merge([
                    'directions' => RubricatorService::getInstance()->getDirections(),
                    'vacancyProfession' => Yii::app()->request->getParam('vacancy-profession'),
                    'workExperience' => Yii::app()->request->getParam('vacancy-work-experience'),
                    'weekdays' => ScheduleService::getInstance()->getSelected(
                        Yii::app()->request->getParam('vacancy-weekday')
                    ),
                    'timeBegin' => Yii::app()->request->getParam('profile-timepicker-begin'),
                    'timeEnd' => Yii::app()->request->getParam('profile-timepicker-end'),
                    'about' => Yii::app()->request->getParam('vacancy-about'),
                    'salary' => Yii::app()->request->getParam('vacancy-salary'),
                    'phone' => Yii::app()->request->getParam('vacancy-phone'),
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
        }
    }


    /**
     * Поиск мастеров и салонов
     *
     * @param integer $directionId Идентификатор направления
     */
    public function actionSearchMasterSalon($directionId)
    {
        try {
            $salons = SearchService::getInstance()->findSalon(
                $this->userId,
                $this->userType,
                $directionId
            );

            $masters = SearchService::getInstance()->findMaster(
                $this->userId,
                $this->userType,
                $directionId,
                null,
                null,
                null,
                null,
                null,
                null
            );

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/MastersSalons.twig',
                array_merge([
                    'salons' => $salons,
                    'masters' => $masters,
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                    'masterRadius' => MASTER_RADIUS_FROM_CLIENT / 1000,
                    'salonRadius' => SALON_RADIUS_FROM_CLIENT / 1000,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/MastersSalons.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                    'masterRadius' => MASTER_RADIUS_FROM_CLIENT / 1000,
                    'salonRadius' => SALON_RADIUS_FROM_CLIENT / 1000,
                ], $this->lkTwigContext)
            );
        }
    }

    /**
     * Поиск мастеров
     *
     * @param integer $directionId Идентификатор направления
     */
    public function actionSearchMaster($directionId)
    {
        try {
            $masters = SearchService::getInstance()->findMaster(
                $this->userId,
                $this->userType,
                $directionId,
                null,
                null,
                null,
                null,
                null,
                null
            );

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Masters.twig',
                array_merge([
                    'masters' => $masters,
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                    'masterRadius' => MASTER_RADIUS_FROM_CLIENT / 1000,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Masters.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                    'masterRadius' => MASTER_RADIUS_FROM_CLIENT / 1000,
                ], $this->lkTwigContext)
            );
        }
    }

    /**
     * Поиск салонов
     *
     * @param integer $directionId Идентификатор направления
     */
    public function actionSearchSalon($directionId)
    {
        try {
            $salons = SearchService::getInstance()->findSalon(
                $this->userId,
                $this->userType,
                $directionId
            );

            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Salons.twig',
                array_merge([
                    'salons' => $salons,
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                    'salonRadius' => SALON_RADIUS_FROM_CLIENT / 1000,
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/' . $this->userType . '/Salons.twig',
                array_merge([
                    'errorText' => $e->getMessage(),
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                    'salonRadius' => SALON_RADIUS_FROM_CLIENT / 1000,
                ], $this->lkTwigContext)
            );
        }
    }

    /**
     * Успешное пополнение баланса через робокассу
     */
    public function actionRoboSuccess()
    {
        try {
            echo (new TemplateEngineTwig)->render(
                'lk/RoboPaySuccess.twig',
                array_merge([
                    'balance' => RoboPayService::getInstance()->getBalance($this->userId),
                ], $this->lkTwigContext)
            );
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render(
                'lk/RoboPaySuccess.twig',
                array_merge([
                    'balance' => 'Неизвестен',
                    'errorText' => $e->getMessage(),
                ], $this->lkTwigContext)
            );
        }
    }

    /**
     * Неуспешное пополнение баланса через робокассу
     */
    public function actionRoboFail()
    {
        echo (new TemplateEngineTwig)->render(
            'lk/RoboPayFail.twig',
            array_merge([
            ], $this->lkTwigContext)
        );
    }

    //Ajax ============================================================================


    /**
     * Удаление фото из портфолио
     * @param integer $id Идентификатор фото в портфолио
     */
    public function actionDeletePortfolioPhoto($id)
    {
        try {
            if (PortfolioService::getInstance()->deletePhoto($this->userId, $this->userType, $id) === false) {
                $this->response('Ошибка удаления фото из портфолио.');
            }

            $this->response('Фото успешно удалено.');
        } catch (PublicMessageException $e) {
            $this->response('Ошибка удаления фото из портфолио: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Удаление видео из обучения
     * @param integer $id Идентификатор фото в портфолио
     */
    public function actionDeleteStudyVideo($id)
    {
        try {
            if (StudyService::getInstance()->deleteVideo($this->userId, $this->userType, $id) === false) {
                $this->response('Ошибка удаления видео из обучения.');
            }

            $this->response('Видео успешно удалено.');
        } catch (PublicMessageException $e) {
            $this->response('Ошибка удаления видео из обучения: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }


    /**
     * Удаление фото из портфолио для админа
     */
    public function actionDeletePortfolioPhotoByAdmin($userId, $userType, $id)
    {
        try {
            if (UserService::getInstance()->checkAccessByAdmin($this->userId) === false) {
                throw new PublicMessageException(ErrorEnum::ACCESS_DENIED);
            }

            if (PortfolioService::getInstance()->deletePhoto($userId, $userType, $id) === false) {
                $this->response('Ошибка удаления фото из портфолио.');
            }

            $this->response('Фото успешно удалено.');
        } catch (PublicMessageException $e) {
            $this->response('Ошибка удаления фото из портфолио: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Создание заказа
     */
    public function actionCreateOrder()
    {
        try {
            $orderId = OrderService::getInstance()->create(
                $this->userId,
                $this->userType,
                Yii::app()->request->getParam('client-services'),
                Yii::app()->request->getParam('order-date'),
                Yii::app()->request->getParam('buf-master-id'),
                Yii::app()->request->getParam('client-address'),
                Yii::app()->request->getParam('client-address-coord'),
                Yii::app()->request->getParam('order-about'),
                Yii::app()->request->getParam('order-cost'),
                Yii::app()->request->getParam('order-place'),
                Yii::app()->request->getParam('order-time')
            );

            if (!empty($orderId)) {
                Yii::app()->request->redirect('/lk/thanks');
            } else {
                echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/CreateOrder.twig', [
                    'errorText' => 'Ошибка создания заказа.',
                ]);
            }
        } catch (PublicMessageException $e) {
            echo (new TemplateEngineTwig)->render('lk/' . $this->userType . '/CreateOrder.twig', [
                'masterBufProfileId' => Yii::app()->request->getParam('buf-master-id'),
                'clientServices' => Yii::app()->request->getParam('client-services'),
                'orderDate' => Yii::app()->request->getParam('order-date'),
                'clientAddress' => Yii::app()->request->getParam('client-address'),
                'clientAddressCoord' => Yii::app()->request->getParam('client-address-coord'),
                'orderAbout' => Yii::app()->request->getParam('order-about'),
                'orderCost' => Yii::app()->request->getParam('order-cost'),
                'orderPlace' => Yii::app()->request->getParam('order-place'),
                'orderTime' => Yii::app()->request->getParam('order-time'),

                'directions' => RubricatorService::getInstance()->getDirections(false),
                'services' => RubricatorService::getInstance()->getServices(),
                'youBtReviewUrls' => ReviewService::getInstance()->getRandVideoReviews(3),
                'yandexGeoKey' => YANDEX_GEOCODER_KEY,
                'errorText' => $e->getMessage(),
            ]);
            Yii::app()->end();
        }
    }

    /**
     * Отмена согласования заказа
     */
    public function actionCancelAgreeOrder()
    {
        $orderId = Yii::app()->request->getParam('order-id');
        $orderComment = Yii::app()->request->getParam('order-comment');

        try {
            $transaction = Yii::app()->db->beginTransaction();
            $orderModel = OrderService::getInstance()->getById($this->userId, $this->userType, $orderId);

            OrderService::getInstance()->cancelAgree($orderId, $this->userId, $this->userType);

            if (!empty($orderComment)) {
                MessageService::getInstance()->create(
                    $this->userId,
                    $this->userType,
                    [$orderModel->owner_user_id],
                    $orderComment,
                    $orderId
                );
            }
            $transaction->commit();

            $this->response('Согласование отменено.', false, $orderId);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка отмены согласования: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Отметка о выполнении заказа
     */
    public function actionExecuteOrder()
    {
        $orderId = Yii::app()->request->getParam('order-id');
        $orderComment = Yii::app()->request->getParam('order-comment');

        try {
            $transaction = Yii::app()->db->beginTransaction();
            $orderModel = OrderService::getInstance()->getById($this->userId, $this->userType, $orderId);

            OrderService::getInstance()->execute($orderId, $this->userId, $this->userType);

            if (!empty($orderComment)) {
                MessageService::getInstance()->create(
                    $this->userId,
                    $this->userType,
                    [$orderModel->owner_user_id],
                    $orderComment,
                    $orderId
                );
            }
            $transaction->commit();

            $this->response('Заказ выполнен.', false, $orderId);
        } catch (PublicMessageException $e) {
            $this->response('Не удалось отметить выполнение заказа: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Удаление заказа
     */
    public function actionDeleteOrder()
    {
        $orderId = Yii::app()->request->getParam('order-id');

        try {
            OrderService::getInstance()->delete($orderId, $this->userId);

            $this->response('Заказ успешно удален.', false, $orderId);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка удаления заказа: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Отправка на согласование к мастеру по данному заказу
     */
    public function actionSendAgreeToMaster()
    {
        $orderId = Yii::app()->request->getParam('order-id');
        $masterProfileId = Yii::app()->request->getParam('master-profile-id');

        try {
            $masterProfileId = OrderService::getInstance()->bindBufMaster(
                $this->userId,
                $orderId,
                $masterProfileId,
                true,
                UserTypeEnum::CLIENT
            );

            $this->response('Заказ успешно отправлен мастерам.', false, $masterProfileId);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка отправки заказа мастерам: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Отправка на согласование к салону по данному заказу
     */
    public function actionSendAgreeToSalon()
    {
        $orderId = Yii::app()->request->getParam('order-id');
        $salonProfileId = Yii::app()->request->getParam('salon-profile-id');

        try {
            $salonProfileId = OrderService::getInstance()->bindBufSalon(
                $this->userId,
                $orderId,
                $salonProfileId,
                true,
                UserTypeEnum::CLIENT
            );

            $this->response('Заказ успешно отправлен салонам.', false, $salonProfileId);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка отправки заказа салонам: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Привязка мастера к заказу по взаимному согласию
     * @return void
     */
    public function actionBindMaster()
    {
        $orderId = Yii::app()->request->getParam('order-id');
        $masterProfileId = Yii::app()->request->getParam('master-profile-id');
        $orderComment = Yii::app()->request->getParam('order-comment');

        try {
            $orderModel = OrderService::getInstance()->getById($this->userId, $this->userType, $orderId);

            $transaction = Yii::app()->db->beginTransaction();
            $masterProfileId = OrderService::getInstance()->bindBufMaster(
                $this->userId,
                $orderId,
                $masterProfileId,
                true,
                $this->userType
            );

            if (!empty($orderComment)) {
                MessageService::getInstance()->create(
                    $this->userId,
                    $this->userType,
                    [$orderModel->owner_user_id],
                    $orderComment,
                    $orderId
                );
            }
            $transaction->commit();

            $this->response('Заказ согласован и готов к исполнению.', false, $masterProfileId);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка согласования заказа: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Привязка салона к заказу по взаимному согласию
     */
    public function actionBindSalon()
    {
        $orderId = Yii::app()->request->getParam('order-id');
        $salonProfileId = Yii::app()->request->getParam('salon-profile-id');
        $orderComment = Yii::app()->request->getParam('order-comment');

        try {
            $orderModel = OrderService::getInstance()->getById($this->userId, $this->userType, $orderId);

            $transaction = Yii::app()->db->beginTransaction();
            $salonProfileId = OrderService::getInstance()->bindBufSalon(
                $this->userId,
                $orderId,
                $salonProfileId,
                true,
                $this->userType
            );

            if (!empty($orderComment)) {
                MessageService::getInstance()->create(
                    $this->userId,
                    $this->userType,
                    [$orderModel->owner_user_id],
                    $orderComment,
                    $orderId
                );
            }
            $transaction->commit();

            $this->response('Заказ согласован и готов к исполнению.', false, $salonProfileId);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка согласования заказа: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Отправка на согласование к клиенту по данному заказу
     */
    public function actionSendAgreeToClient()
    {
        $orderId = Yii::app()->request->getParam('order-id');
        $masterProfileId = Yii::app()->request->getParam('master-profile-id');
        $salonProfileId = Yii::app()->request->getParam('salon-profile-id');
        $orderComment = Yii::app()->request->getParam('order-comment');

        if ($masterProfileId === null && $salonProfileId === null) {
            $this->response('Ошибка отправки предложения клиенту: Отсутвуют входные параметры.', true);
            Yii::app()->end();
        }

        try {
            $transaction = Yii::app()->db->beginTransaction();
            $orderModel = OrderService::getInstance()->getById($this->userId, $this->userType, $orderId);

            if ($masterProfileId !== null) {
                OrderService::getInstance()->bindBufMaster(
                    $this->userId,
                    $orderId,
                    $masterProfileId,
                    true,
                    UserTypeEnum::MASTER
                );
            } elseif ($salonProfileId !== null) {
                OrderService::getInstance()->bindBufSalon(
                    $this->userId,
                    $orderId,
                    $salonProfileId,
                    true,
                    UserTypeEnum::SALON
                );
            }

            if (!empty($orderComment)) {
                MessageService::getInstance()->create(
                    $this->userId,
                    $this->userType,
                    [$orderModel->owner_user_id],
                    $orderComment,
                    $orderId
                );
            }
            $transaction->commit();

            $this->response('Предложение успешно отправлено клиенту.', false);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка отправки предложения клиенту: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Поиск мастера
     */
    public function actionFindMaster()
    {
        try {
            $masters = SearchService::getInstance()->findMaster(
                $this->userId,
                $this->userType,
                Yii::app()->request->getParam('direction-id'),
                Yii::app()->request->getParam('service-id'),
                Yii::app()->request->getParam('fio'),
                Yii::app()->request->getParam('work-experience'),
                Yii::app()->request->getParam('subway'),
                Yii::app()->request->getParam('addressCoord'),
                Yii::app()->request->getParam('sort')
            );

            $masterHtml = (new TemplateEngineTwig)->render('lk/FindedMasters.twig', array_merge([
                    'masters' => $masters,
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                ], $this->lkTwigContext)
            );

            $this->response($masterHtml, false, '', false);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка поиска мастера: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Поиск салона
     */
    public function actionFindSalon()
    {
        try {
            $salons = SearchService::getInstance()->findSalon(
                $this->userId,
                $this->userType,
                Yii::app()->request->getParam('direction-id'),
                Yii::app()->request->getParam('service-id'),
                Yii::app()->request->getParam('subway'),
                Yii::app()->request->getParam('addressCoord'),
                Yii::app()->request->getParam('sort')
            );

            $masterHtml = (new TemplateEngineTwig)->render('lk/FindedSalons.twig', array_merge([
                    'salons' => $salons,
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                ], $this->lkTwigContext)
            );

            $this->response($masterHtml, false, '', false);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка поиска салона: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }


    /**
     * Получение отзывов по заказу
     *
     * @param integer $orderId идентификатор заказа
     */
    public function actionGetReviews($orderId)
    {
        try {
            $reviews = ReviewService::getInstance()->getByOrder(
                $orderId
            );

            $this->response('Отзывы успешно получены.', false, json_encode($reviews));
        } catch (PublicMessageException $e) {
            $this->response('Ошибка получения списка отзывов для заказа: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Создание отзыва к заказу
     */
    public function actionCreateReview()
    {
        $orderId = Yii::app()->request->getParam('order_id');
        $assessment = Yii::app()->request->getParam('assessment');
        $reviewText = Yii::app()->request->getParam('review_text');

        try {
            ReviewService::getInstance()->create(
                $this->userId,
                $this->userType,
                $orderId,
                $assessment,
                $reviewText
            );

            $this->response('Отзыв создан.');
        } catch (PublicMessageException $e) {
            $this->response('Ошибка сохранения отзыва для заказа: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Создание сообщения пользователю
     */
    public function actionCreateMessage()
    {
        $message = Yii::app()->request->getParam('message');
        $masterProfileId = Yii::app()->request->getParam('master-id');
        $salonProfileId = Yii::app()->request->getParam('salon-id');

        if (($masterProfileId === null && $salonProfileId === null) || $message === null) {
            $this->response('Ошибка отправки сообщения: Отсутвуют входные параметры.', true);
            Yii::app()->end();
        }

        try {
            if ($masterProfileId !== null) {
                $profileModel = ProfileService::getInstance()->getProfileModel(
                    $masterProfileId,
                    UserTypeEnum::MASTER,
                    null);
            } elseif ($salonProfileId !== null) {
                $profileModel = ProfileService::getInstance()->getProfileModel(
                    $salonProfileId,
                    UserTypeEnum::SALON,
                    null);
            }

            MessageService::getInstance()->create(
                $this->userId,
                $this->userType,
                [$profileModel->user_id],
                $message
            );

            $this->response('Сообщение отправлено.', false, $profileModel->id);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка отправки сообщения: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Создание сообщения пользователю и возрат его для отображения
     */
    public function actionCreateAndReturnMessage()
    {
        $message = Yii::app()->request->getParam('message');
        $masterProfileId = Yii::app()->request->getParam('master-id');
        $salonProfileId = Yii::app()->request->getParam('salon-id');
        $userId = Yii::app()->request->getParam('user-id');

        if (($masterProfileId === null && $salonProfileId === null && $userId === null) || $message === null) {
            $this->response('Ошибка отправки сообщения: Отсутвуют входные параметры.', true);
            Yii::app()->end();
        }

        try {
            if ($userId !== null) {
                $messageArray = MessageService::getInstance()->create(
                    $this->userId,
                    $this->userType,
                    [$userId],
                    $message
                );
            } else {
                if ($masterProfileId !== null) {
                    $profileModel = ProfileService::getInstance()->getProfileModel(
                        $masterProfileId,
                        UserTypeEnum::MASTER,
                        null);
                } elseif ($salonProfileId !== null) {
                    $profileModel = ProfileService::getInstance()->getProfileModel(
                        $salonProfileId,
                        UserTypeEnum::SALON,
                        null);
                }

                $messageArray = MessageService::getInstance()->create(
                    $this->userId,
                    $this->userType,
                    [$profileModel->user_id],
                    $message
                );
            }

            $masterHtml = (new TemplateEngineTwig)->render('lk/CreatedMessage.twig', array_merge([
                    'message' => $messageArray,
                    'imgSmallPhotoWidth' => IMG_SMALL_WIDTH,
                ], $this->lkTwigContext)
            );

            $this->response($masterHtml, false, '', false);
        } catch (PublicMessageException $e) {
            $this->response('Ошибка отправки сообщения: ' . $e->getMessage(), true);
            Yii::app()->end();
        }
    }

    /**
     * Результат пополнения баланса через робокассу
     */
    public function actionRoboResult()
    {
        echo RoboPayService::getInstance()->resultPay(Yii::app()->request);
    }
}
