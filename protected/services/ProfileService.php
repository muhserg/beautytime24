<?php

/**
 * Обеспечивает работы с профилями пользователя
 */
class ProfileService
{
    private static $profileService = null;

    /** var ImageHelper */
    private $imageHelper;

    /**
     * @return ProfileService
     */
    public static function getInstance()
    {
        if (self::$profileService === null) {
            self::$profileService = new self();
        }
        return self::$profileService;
    }

    public function __construct()
    {
        $this->imageHelper = new ImageHelper();
    }


    /**
     * Получение профилей пользователей для модерации
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     *
     * @return string
     * @throws PublicMessageException
     */
    public function getForModerate($userId, $userType)
    {
        try {
            return join(', ', (new MasterProfileDsp)->findForModerate());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not open getForModerate. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось открыть список для модерации. Ошибка в БД.');
        }
    }


    /**
     * Получение профиля пользователя
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     * @param string $orderServicesBy сортировка услуг
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getProfile($userId, $userType, $orderServicesBy = 'name ASC')
    {
        try {
            $directions = RubricatorService::getInstance()->getDirections();
            $services = RubricatorService::getInstance()->getServices($orderServicesBy);

            $profileModel = $this->getProfileModel(null, $userType, $userId);
            //если профиль пока пустой
            if ($profileModel === null) {
                return [
                    'yandexGeoCoord' => YANDEX_GEOCODER_COORD,
                    'yandexGeoZoom' => YANDEX_GEOCODER_ZOOM,
                    'profileDirections' => $directions,
                    'profileServices' => $services,
                    'smsConfirm' => true,
                ];
            }
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not open profile. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось открыть профайл. Ошибка в БД.');
        }

        $profileFields = [
            'address' => $profileModel->address,
            'addressCoord' => $profileModel->address_coord,
            'nearSubway' => $profileModel->near_subway,
            'phone' => (new PhoneHelper)->formatPhoneForView($profileModel->phone),
            'phoneImg' => ImageHelper::createImageFromString(
                (new PhoneHelper)->formatPhoneForView($profileModel->phone),
                (isset(Yii::app()->session['isMobile']) ? Yii::app()->session['isMobile'] : false),
                ImgLabelTypeEnum::LONG_TEXT
            ),

            'yandexGeoCoord' => !empty($profileModel->address_coord) ? $profileModel->address_coord : YANDEX_GEOCODER_COORD,
            'yandexGeoZoom' => !empty($profileModel->address_coord) ? YANDEX_GEOCODER_ADDRESS_ZOOM : YANDEX_GEOCODER_ZOOM,
            'youtubeUrl' => !empty($profileModel->youtube_url) ? $profileModel->youtube_url : null,
            'youtubeUrlEmbed' => !empty($profileModel->youtube_url) ? (strpos($profileModel->youtube_url,
                    YOUTUBE_EMBED_PATH) === 0) : false,

            'profileDirections' => $directions,
            'profileServices' => $services,
            'smsConfirm' => !empty($profileModel->sms_confirm) ? $profileModel->sms_confirm : '',
        ];

        if ($userType === UserTypeEnum::CLIENT) {
            return array_merge($profileFields, [
                'firstName' => $profileModel->first_name,
                'lastName' => $profileModel->last_name,
                'middleName' => $profileModel->middle_name,
                'avatarUrl' => !empty($profileModel->avatar_file_name) ? IMG_DIR . $profileModel->avatar_file_name : null,
                'about' => $profileModel->about,
            ]);
        } elseif ($userType === UserTypeEnum::MASTER) {
            $profileFields = RubricatorService::getInstance()->getSelectedDirectionsAndServices($userType,
                $profileModel->id, $profileFields);

            return array_merge($profileFields, [
                'firstName' => $profileModel->first_name,
                'lastName' => $profileModel->last_name,
                'middleName' => $profileModel->middle_name,
                'avatarUrl' => !empty($profileModel->avatar_file_name) ? IMG_DIR . $profileModel->avatar_file_name : null,
                'about' => $profileModel->about,
                'rating' => $profileModel->rating,
                'workExperience' => $profileModel->work_experience,
                'isVacancy' => (IntVal($profileModel->is_vacancy) === VacancyEnum::FIND_WORK ? true : false),
                'place' => $profileModel->place,
            ]);
        } elseif ($userType === UserTypeEnum::SALON) {
            $profileFields = RubricatorService::getInstance()->getSelectedDirectionsAndServices($userType,
                $profileModel->id, $profileFields);

            return array_merge($profileFields, [
                'firstName' => $profileModel->gendir_first_name,
                'lastName' => $profileModel->gendir_last_name,
                'middleName' => $profileModel->gendir_middle_name,
                'avatarUrl' => !empty($profileModel->logo_file_name) ? IMG_DIR . $profileModel->logo_file_name : null,
                'about' => $profileModel->description,
                'rating' => $profileModel->rating,
                'inn' => $profileModel->inn,
                'companyName' => $profileModel->name,
                'urName' => $profileModel->ur_name,
                'place' => $profileModel->place,
            ]);
        }


        return [
            'yandexGeoCoord' => YANDEX_GEOCODER_COORD,
            'yandexGeoZoom' => YANDEX_GEOCODER_ZOOM,
        ];
    }


    /**
     * Получение телефона пользователя
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     *
     * @return string
     * @throws PublicMessageException
     */
    public function getPhone($userId, $userType)
    {
        try {
            $profileModel = $this->getProfileModel(null, $userType, $userId);
            if ($profileModel === null) {
                throw new PublicMessageException('Заполните профиль.');
            }

            return $profileModel->phone;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not open phone profile. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось открыть телефон профиля. Ошибка в БД.');
        }
    }

    /**
     * Сохранение профиля пользователя
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     * @param array $profileSettings параметры профиля пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function save($userId, $userType, $profileSettings)
    {
        if (empty($profileSettings)) {
            throw new PublicMessageException('Отсутствуют входные параметры.');
        }

        try {
            //получаем модель
            $profileModel = $this->getProfileModel(null, $userType, $userId);
            if ($profileModel === null) {
                $profileModel = $this->createProfileModel($userType);
            }
            if ($profileModel === null) {
                throw new PublicMessageException('Тип профиля не найден.');
            }

            //заполняем модель
            $profileModel->user_id = $userId;
            $profileModel->sms_confirm = (isset($profileSettings['smsConfirm']) && $profileSettings['smsConfirm'] === 'on');
            if ($userType === UserTypeEnum::CLIENT || $userType === UserTypeEnum::MASTER) {
                $profileModel->first_name = $profileSettings['firstName'];
                $profileModel->last_name = $profileSettings['lastName'];
                $profileModel->middle_name = $profileSettings['middleName'];
                $profileModel->address = $profileSettings['address'];
                $profileModel->address_coord = $profileSettings['addressCoord'];
                $profileModel->fio_address_hash = md5($profileModel->first_name .
                    $profileModel->last_name .
                    $profileModel->middle_name .
                    $profileModel->address
                );

                if ($userType === UserTypeEnum::CLIENT) {
                    $profileModel->phone = (new PhoneHelper)->formatPhoneForSave($profileSettings['phone']);
                }

                if ($userType === UserTypeEnum::MASTER) {
                    if(isset($profileSettings['phone']) && $profileSettings['phone'] !== null){
                        $profileModel->phone = (new PhoneHelper)->formatPhoneForSave($profileSettings['phone']);
                    }

                    $profileModel->place = $profileSettings['profilePlace'];
                    $profileModel->work_experience = $profileSettings['workExperience'];
                    $profileModel->is_vacancy = $profileSettings['is_vacancy'];
                    if (isset($profileSettings['youtubeUrl'])) {
                        $profileModel->youtube_url = $profileSettings['youtubeUrl'];
                    }
                }
                $profileModel->about = $profileSettings['about'];

                //аватар мастера или клиента
                if (!empty($profileSettings['photo']['tmp_name'])) {
                    //перемещаем существующее фото в архив
                    $this->imageHelper->archiveImage($profileModel->avatar_file_name);
                    $this->imageHelper->deleteImage($profileModel->small_avatar_file_name);
                    $profileModel->small_avatar_file_name = $this->imageHelper->getSmallImage($profileSettings['photo']);
                    $profileModel->avatar_file_name = $this->imageHelper->uploadImage($profileSettings['photo']);
                }
            } elseif ($userType === UserTypeEnum::SALON) {
                if(isset($profileSettings['phone']) && $profileSettings['phone'] !== null){
                    $profileModel->phone = (new PhoneHelper)->formatPhoneForSave($profileSettings['phone']);
                }

                $profileModel->name = $profileSettings['companyName'];
                $profileModel->ur_name = $profileSettings['companyName'];
                $profileModel->inn = $profileSettings['inn'];
                $profileModel->gendir_first_name = $profileSettings['firstName'];
                $profileModel->gendir_last_name = $profileSettings['lastName'];
                $profileModel->gendir_middle_name = $profileSettings['middleName'];
                $profileModel->address = $profileSettings['address'];
                $profileModel->address_coord = $profileSettings['addressCoord'];
                $profileModel->name_address_hash = md5($profileModel->name . $profileModel->address);

                $profileModel->description = $profileSettings['about'];
                $profileModel->place = $profileSettings['profilePlace'];
                if (isset($profileSettings['youtubeUrl'])) {
                    $profileModel->youtube_url = $profileSettings['youtubeUrl'];
                }

                //логотип компании
                if (!empty($profileSettings['photo']['tmp_name'])) {
                    $this->imageHelper->archiveImage($profileModel->logo_file_name);
                    $this->imageHelper->deleteImage($profileModel->small_logo_file_name);
                    $profileModel->small_logo_file_name = $this->imageHelper->getSmallImage($profileSettings['photo']);
                    $profileModel->logo_file_name = $this->imageHelper->uploadImage($profileSettings['photo']);
                }
            } else {
                throw new PublicMessageException('Тип профиля для сохранения не найден.');
            }

            //определяем ближайшую станцию метро и записываем раздельно долготу и широту
            if (!empty($profileSettings['addressCoord'])) {
                list($lat, $lng) = explode(',', $profileSettings['addressCoord']);
                $profileModel->latitude = deg2rad($lat);
                $profileModel->longitude = deg2rad($lng);
                $profileModel->near_subway = $this->getNearSubway($lat, $lng);
            }

            if (!$profileModel->save()) {
                BtLogger::getLogger()->error('Can not save profile.', [
                    'profileSettings' => $profileSettings,
                    'error' => $profileModel->getErrors(),
                ]);

                if (!empty($profileSettings['photo']['tmp_name'])) {
                    $this->imageHelper->archiveImage($profileModel->avatar_file_name);
                }
                throw new PublicMessageException('Не удалось сохранить профайл.');
            }

            if ($userType === UserTypeEnum::MASTER || $userType === UserTypeEnum::SALON) {
                RubricatorService::getInstance()->updateDirections($userType, $profileModel->id, $userId,
                    $profileSettings['selectedDirections']);
                RubricatorService::getInstance()->updateServices($userType, $profileModel->id, $userId,
                    $profileSettings['selectedServices']);
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save profile. Fatal error.', [
                'profileSettings' => $profileSettings,
                'photoData' => isset($profileSettings['photo']) ? $profileSettings['photo'] : null,
                'error' => $e,
            ]);
            if (!empty($profileSettings['photo']['tmp_name'])) {
                if (isset($profileModel->small_avatar_file_name)) {
                    $this->imageHelper->deleteImage($profileModel->small_avatar_file_name);
                    $this->imageHelper->archiveImage($profileModel->avatar_file_name);
                }
                if (isset($profileModel->logo_file_name)) {
                    $this->imageHelper->deleteImage($profileModel->small_logo_file_name);
                    $this->imageHelper->archiveImage($profileModel->logo_file_name);
                }
            }

            if (strpos($e->getMessage(), 'Duplicate entry') > 0) {
                if (strpos($e->getMessage(), 'for key \'idx_ui_salon_profile$name_address_hash\'') > 0) {
                    throw new PublicMessageException('Пользователь с таким именем и адресом уже зарегистрирован в системе. ');
                }

                throw new PublicMessageException('Пользователь уже зарегистрирован в системе. ');
            }

            throw new PublicMessageException('Не удалось сохранить профайл. Ошибка в БД.');
        }
    }

    /**
     * Создание модели профиля пользователя в зависимости от типа
     *
     * @param string $profileType тип профиля
     * @return Null|ClientProfileModel|MasterProfileModel|SalonProfileModel|PromoterProfileModel|ProviderProfileModel
     */
    public function createProfileModel($profileType)
    {
        if ($profileType === UserTypeEnum::CLIENT) {
            return new ClientProfileModel;
        } elseif ($profileType === UserTypeEnum::MASTER) {
            return new MasterProfileModel;
        } elseif ($profileType === UserTypeEnum::SALON) {
            return new SalonProfileModel;
        } elseif ($profileType === UserTypeEnum::PROMOTER) {
            return new PromoterProfileModel;
        } elseif ($profileType === UserTypeEnum::PROVIDER) {
            return new ProviderProfileModel;
        } else {
            return null;
        }
    }


    /**
     * Сохранение телефона пользователя
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     * @param string $phone телефон пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function savePhone($userId, $userType, $phone)
    {
        if (empty($phone)) {
            throw new PublicMessageException('Отсутствует номер телефона.');
        }

        try {
            //получаем модель
            $profileModel = $this->getProfileModel(null, $userType, $userId);
            if ($profileModel === null) {
                throw new PublicMessageException('Профиль не найден.');
            }

            //заполняем модель
            $profileModel->phone = (new PhoneHelper)->formatPhoneForSave($phone);

            if (!$profileModel->save()) {
                BtLogger::getLogger()->error('Can not save profile.', [
                    'phone' => $phone,
                    'error' => $profileModel->getErrors(),
                ]);
                throw new PublicMessageException('Не удалось сохранить номер телефона.');
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save profile. Fatal error.', [
                'phone' => $phone,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось сохранить номер телефона. Ошибка в БД.');
        }
    }

    /**
     * Получение модели профиля пользователя в зависимости от типа и идентификатора
     *
     * @param integer $profileId тип профиля
     * @param string $profileType тип профиля
     * @param string $profileType тип профиля
     * @return Null|ClientProfileModel|MasterProfileModel|SalonProfileModel|PromoterProfileModel|ProviderProfileModel
     *
     * @throws PublicMessageException
     */
    public function getProfileModel($profileId, $profileType, $userId = null)
    {
        try {
            if ($userId === null) {
                $profileId = IntVal($profileId);
                if ($profileType === UserTypeEnum::CLIENT) {
                    return ClientProfileModel::model()->findByPk($profileId);
                } elseif ($profileType === UserTypeEnum::MASTER) {
                    return MasterProfileModel::model()->findByPk($profileId);
                } elseif ($profileType === UserTypeEnum::SALON) {
                    return SalonProfileModel::model()->findByPk($profileId);
                } elseif ($profileType === UserTypeEnum::PROMOTER) {
                    return PromoterProfileModel::model()->findByPk($profileId);
                } elseif ($profileType === UserTypeEnum::PROVIDER) {
                    return ProviderProfileModel::model()->findByPk($profileId);
                } else {
                    return null;
                }
            }

            $userId = IntVal($userId);
            if ($profileType === UserTypeEnum::CLIENT) {
                return ClientProfileModel::model()->findByAttributes(['user_id' => $userId]);
            } elseif ($profileType === UserTypeEnum::MASTER) {
                return MasterProfileModel::model()->findByAttributes(['user_id' => $userId]);
            } elseif ($profileType === UserTypeEnum::SALON) {
                return SalonProfileModel::model()->findByAttributes(['user_id' => $userId]);
            } elseif ($profileType === UserTypeEnum::PROMOTER) {
                return PromoterProfileModel::model()->findByAttributes(['user_id' => $userId]);
            } elseif ($profileType === UserTypeEnum::PROVIDER) {
                return ProviderProfileModel::model()->findByAttributes(['user_id' => $userId]);
            } else {
                return null;
            }

        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get profile. Fatal error.', [
                'user_id' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить профиль. Ошибка в БД.');
        }

    }


    /**
     * Определяет достаточно ли заполнен профиль
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function isComplete($userId, $userType)
    {
        $profileModel = $this->getProfileModel(null, $userType, $userId);
        if ($profileModel === null) {
            return false;
        }
        if ($userType === UserTypeEnum::MASTER) {
            $selectedServices = MasterProfileServiceModel::model()->findAllByAttributes(['profile_id' => $profileModel->id]);
        } elseif ($userType === UserTypeEnum::SALON) {
            $selectedServices = SalonProfileServiceModel::model()->findAllByAttributes(['profile_id' => $profileModel->id]);
        }

        return isset($selectedServices) && (count($selectedServices) > 0);
    }


    /**
     * Рассчитывает и сохраняет рейтинг пользователя
     *
     * @param integer $profileId идентификатор профиля пользователя
     * @param string $userType тип пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function calcRating($profileId, $userType)
    {
        try {
            //получаем модель
            $profileModel = $this->getProfileModel($profileId, $userType, null);
            if ($profileModel === null) {
                throw new PublicMessageException('Профиль для расчета рейтинга не найден.');
            }

            if ($userType === UserTypeEnum::CLIENT) {
                //Нет расчета рейтинга
            } elseif ($userType === UserTypeEnum::MASTER) {
                $profileModel->rating = (new MasterProfileDsp)->calcRating($profileId);
            } elseif ($userType === UserTypeEnum::SALON) {
                $profileModel->rating = (new SalonProfileDsp)->calcRating($profileId);
            }

            if (!$profileModel->save()) {
                BtLogger::getLogger()->error('Can not save rating profile.', [
                    'profileId' => $profileId,
                    'userType' => $userType,
                    'error' => $profileModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось рассчитать рейтинг.');
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save rating profile. Fatal error.', [
                'userId' => $profileId,
                'userType' => $userType,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось рассчитать рейтинг. Ошибка в БД.');
        }
    }

    /**
     * Возвращает ближайшую станцию метро
     *
     * @param float $lat широта
     * @param float $lng долгота
     *
     * @return string
     * @throws PublicMessageException
     */
    public function getNearSubway($lat, $lng)
    {
        $geoCoderUrl = 'https://geocode-maps.yandex.ru/1.x/?format=json&apikey=' . YANDEX_GEOCODER_KEY .
            '&kind=metro&results=1&geocode=' . $lng . ',' . $lat;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $geoCoderUrl);
        // Pass TRUE or 1 if you want to wait for and catch the response against the request made
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // For Debug mode; shows up any error encountered during the operation
        curl_setopt($curl, CURLOPT_VERBOSE, 1);
        $response = curl_exec($curl);

        if (empty($response)) {
            BtLogger::getLogger()->error('Can not find near subway.', [
                'geoCoderUrl' => $geoCoderUrl,
                'lat' => $lat,
                'lng' => $lng,
            ]);

            return '';
        } else {
            $responseJson = json_decode($response, true);
            return str_replace(
                'метро ',
                '',
                $responseJson['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['name']
            );
        }
    }
}
