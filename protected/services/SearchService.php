<?php

/**
 * Сервис для поиска мастеров и салонов
 */
class SearchService
{
    private static $searchService = null;

    /**
     * @return SearchService
     */
    public static function getInstance()
    {
        if (self::$searchService === null) {
            self::$searchService = new self();
        }
        return self::$searchService;
    }

    /**
     * Поиск салонов по критерию
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     * @param integer $directionId Идентификатор направления
     * @param integer $serviceId Идентификатор услуги
     * @param string $subway Станция метро
     * @param string $address Адрес поиска
     * @param string $addressCoord Координаты адреса
     * @param string $sort сортировка списка мастеров
     *
     * @return array
     * @throws PublicMessageException
     */
    public function findSalon($userId, $userType, $directionId, $serviceId, $subway, $addressCoord, $sort)
    {
        try {
            if (empty($userId)) {
                throw new PublicMessageException('Пользователь не авторизован.');
            }

            $userModel = ProfileService::getInstance()->getProfileModel(null, $userType, $userId);
            if (empty($userModel)) {
                throw new PublicMessageException('Заполните свой профиль.');
            }

            if (empty($addressCoord)) {
                $salons = (new SalonProfileDsp)->findSalon(
                    $directionId,
                    $serviceId,
                    $userModel->latitude,
                    $userModel->longitude,
                    null,
                    null,
                    $subway,
                    $sort);
            } else {
                list($lat, $lng) = explode(',', $addressCoord);
                $salons = (new SalonProfileDsp)->findSalon(
                    $directionId,
                    $serviceId,
                    $userModel->latitude,
                    $userModel->longitude,
                    deg2rad($lat),
                    deg2rad($lng),
                    $subway,
                    $sort
                );
            }

            return $this->getFullArraySalonsToOrder($salons, $userModel->address_coord);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get salons. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список салонов. Ошибка в БД.');
        }
    }


    /**
     * Поиск мастеров по критерию
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     * @param integer $directionId Идентификатор направления
     * @param integer $serviceId Идентификатор услуги
     * @param string $fio ФИО мастера
     * @param string $workExperiencePeriod стаж работы в диапазоне
     * @param string $subway Станция метро
     * @param string $address Адрес поиска
     * @param string $addressCoord Координаты адреса
     * @param string $sort сортировка списка мастеров
     *
     * @return array
     * @throws PublicMessageException
     */
    public function findMaster(
        $userId,
        $userType,
        $directionId,
        $serviceId,
        $fio,
        $workExperiencePeriod,
        $subway,
        $addressCoord,
        $sort
    )
    {
        if (empty($userId)) {
            throw new PublicMessageException('Пользователь не авторизован.');
        }

        try {
            $userModel = ProfileService::getInstance()->getProfileModel(null, $userType, $userId);
            if (empty($userModel)) {
                throw new PublicMessageException('Заполните свой профиль.');
            }

            if (empty($addressCoord)) {
                $masters = (new MasterProfileDsp)->findMaster(
                    $directionId,
                    $serviceId,
                    $fio,
                    $userModel->latitude,
                    $userModel->longitude,
                    null,
                    null,
                    $workExperiencePeriod,
                    $subway,
                    $sort);
            } else {
                list($lat, $lng) = explode(',', $addressCoord);
                $masters = (new MasterProfileDsp)->findMaster(
                    $directionId,
                    $serviceId,
                    $fio,
                    $userModel->latitude,
                    $userModel->longitude,
                    deg2rad($lat),
                    deg2rad($lng),
                    $workExperiencePeriod,
                    $subway,
                    $sort
                );
            }

            return $this->getFullArrayMastersToOrder($masters, $addressCoord);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get masters. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список мастеров. Ошибка в БД.');
        }
    }

    /**
     * Поиск мастеров для привязки к заказу
     *
     * @param ClientProfileModel|SalonProfileModel $clientOrSalonModel данные профиля пользователя (это либо клиент, либо салон при заказе клиента через оператора салона)
     * @param OrderModel $orderModel данные заказа
     *
     * @return array
     * @throws PublicMessageException
     */
    public function findMasterByOrder($clientOrSalonModel, OrderModel $orderModel)
    {
        try {
            if ($orderModel->owner_user_id !== $clientOrSalonModel->user_id) {
                throw new PublicMessageException('Заказ не принадлежит данному пользователю.');
            }

            $masters = (new MasterProfileDsp)->findMasterByOrder(
                $orderModel->main_service_id,
                $orderModel->receipt_date,
                (!empty($orderModel->latitude) ? $orderModel->latitude : $clientOrSalonModel->latitude),
                (!empty($orderModel->longitude) ? $orderModel->longitude : $clientOrSalonModel->longitude),
                (!empty($orderModel->near_subway) ? $orderModel->near_subway : $clientOrSalonModel->near_subway)
            );
            if (empty($masters)) {
                return [];
            }

            return $this->getFullArrayMastersToOrder(
                $masters,
                (!empty($orderModel->address_coord) ? $orderModel->address_coord : $clientOrSalonModel->address_coord)
            );
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get masters. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список мастеров. Ошибка в БД.');
        }
    }

    /**
     * Поиск салонов для привязки к заказу
     *
     * @param ClientProfileModel $clientModel данные профиля пользователя
     * @param OrderModel $orderModel данные заказа
     *
     * @return array
     * @throws PublicMessageException
     */
    public function findSalonByOrder(ClientProfileModel $clientModel, OrderModel $orderModel)
    {
        try {
            if ($orderModel->owner_user_id !== $clientModel->user_id) {
                throw new PublicMessageException('Заказ не принадлежит данному пользователю.');
            }

            $salons = (new SalonProfileDsp)->findSalonByOrder(
                $orderModel->main_service_id,
                $orderModel->receipt_date,
                (!empty($orderModel->latitude) ? $orderModel->latitude : $clientModel->latitude),
                (!empty($orderModel->longitude) ? $orderModel->longitude : $clientModel->longitude),
                (!empty($orderModel->near_subway) ? $orderModel->near_subway : $clientModel->near_subway)
            );
            if (empty($salons)) {
                return [];
            }

            return $this->getFullArraySalonsToOrder(
                $salons,
                (!empty($orderModel->address_coord) ? $orderModel->address_coord : $clientModel->address_coord)
            );
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get salons. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список салонов. Ошибка в БД.');
        }
    }

    /**
     * Определяет расстояние от клиента до мастера (салона)
     *
     * @param string $clientAddressCoord координаты адреса клиента
     * @param string $masterOrSalonAddressCoord координаты адреса мастера (салона)
     * @return string
     * @throws PublicMessageException
     */
    public function getDistance(
        $clientAddressCoord,
        $masterOrSalonAddressCoord
    )
    {
        try {
            if (!empty($clientAddressCoord) && !empty($masterOrSalonAddressCoord)) {
                list($clientLat, $clientLng) = explode(',', $clientAddressCoord);
                list($masterOrSalonLat, $masterOrSalonLng) = explode(',', $masterOrSalonAddressCoord);
                return (new GeoHelper)->formatter(
                    (new GeoHelper)->distance(
                        $clientLat,
                        $clientLng,
                        $masterOrSalonLat,
                        $masterOrSalonLng
                    )
                );
            }
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not cals distance from cilent to master or salon. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось рассчитать дистанцию от клиента до мастера или салона. Ошибка в БД.');
        }
    }

    /**
     * Выдает оказываемые услуги мастера
     *
     * @param integer $masterId идентификатор профайла мастера
     *
     * @return string
     * @throws PublicMessageException
     */
    public function getMasterServicesList($masterId)
    {
        $masterServiceModels = MasterProfileServiceModel::model()->with([
            'service' => [
                'select' => 'name',
                'joinType' => 'INNER JOIN',
                'condition' => 'service.id = service_id',
            ],
        ])->findAll([
            'select' => 'profile_id, service_id, cost',
            'condition' => 'profile_id = :profile_id',
            'params' => ['profile_id' => $masterId],
            'order' => 'service.name',
        ]);
        $masterServices = [];
        foreach ($masterServiceModels as $masterServiceModel) {
            if ($masterServiceModel->cost > 0) {
                $masterServices[] = $masterServiceModel->service->name . ' (' . $masterServiceModel->cost . ' р.)';
            } else {
                $masterServices[] = $masterServiceModel->service->name;
            }
        }

        $masterAdditionalServiceModels = MasterAdditionalServiceModel::model()->findAllByAttributes([
            'profile_id' => $masterId,
        ], [
            'condition' => 'cost IS NOT NULL AND cost > 0',
        ]);

        $masterAdditionalServices = [];
        foreach ($masterAdditionalServiceModels as $masterAdditionalServiceModel) {
            $masterAdditionalServices[] = $masterAdditionalServiceModel->name . ' (' . $masterAdditionalServiceModel->cost . ' р.)';
        }

        return implode(', ', $masterAdditionalServices) . "\nОсновные: " . implode(', ', $masterServices);
    }


    /**
     * Выдает оказываемые услуги салона
     *
     * @param integer $salonId идентификатор профайла салона
     *
     * @return string
     * @throws PublicMessageException
     */
    public function getSalonServicesList($salonId)
    {
        $salonServiceModels = SalonProfileServiceModel::model()->with([
            'service' => [
                'select' => 'name',
                'joinType' => 'INNER JOIN',
                'condition' => 'service.id = service_id',
            ],
        ])->findAll([
            'select' => 'profile_id, service_id, cost',
            'condition' => 'profile_id = :profile_id',
            'params' => ['profile_id' => $salonId],
            'order' => 'service.name',
        ]);
        $salonServices = [];
        foreach ($salonServiceModels as $salonServiceModel) {
            if ($salonServiceModel->cost > 0) {
                $salonServices[] = $salonServiceModel->service->name . ' (' . $salonServiceModel->cost . ' р.)';
            } else {
                $salonServices[] = $salonServiceModel->service->name;
            }
        }

        $salonAdditionalServiceModels = SalonAdditionalServiceModel::model()->findAllByAttributes([
            'profile_id' => $salonId,
        ], [
            'condition' => 'cost IS NOT NULL AND cost > 0',
        ]);

        $salonAdditionalServices = [];
        foreach ($salonAdditionalServiceModels as $salonAdditionalServiceModel) {
            $salonAdditionalServices[] = $salonAdditionalServiceModel->name . ' (' . $salonAdditionalServiceModel->cost . ' р.)';
        }

        return implode(', ', $salonAdditionalServices) . "\nОсновные: " . implode(', ', $salonServices);
    }

    /**
     * Список случайных мастеров
     *
     * @param integer $count кол-во мастеров, для вывода
     * @return array
     * @throws PublicMessageException
     */
    public function randMasters($count)
    {
        try {
            return $this->getFullArrayMastersToOrder(
                (new MasterProfileDsp)->findRandMaster($count)
            );
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get masters. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список мастеров. Ошибка в БД.');
        }
    }

    /**
     * Выдает массив мастеров для отображения
     *
     * @param array $masters данные мастеров
     * @param string $addressCoord координаты клиента
     *
     * @return array
     * @throws Exception
     */
    public function getFullArrayMastersToOrder(array $masters, $addressCoord = null)
    {
        foreach ($masters as $num => $master) {
            $masters[$num]['avatarUrl'] = !empty($master['small_avatar_file_name'])
                ? IMG_SMALL_DIR . $master['small_avatar_file_name'] : IMG_PATH_NO_AVATAR;

            $masters[$num]['services_list'] = $this->getMasterServicesList($master['id']);
            $masters[$num]['portfolio'] = PortfolioService::getInstance()->getPortfolio(
                $master['user_id'],
                UserTypeEnum::MASTER
            );

            $masters[$num]['fio'] = $master['first_name'] . ' ' . $master['last_name'] . ' ' . $master['middle_name'];
            $masters[$num]['active'] = StatisticService::getInstance()->userActivity(
                null,
                $master['last_hit_time']
            );

            $masters[$num]['img_class'] = ($masters[$num]['avatarUrl'] !== IMG_PATH_NO_AVATAR ? 'round_photo' : 'round_no_photo');
            if (!empty($master['phone'])) {
                $masters[$num]['phoneImg'] = ImageHelper::createImageFromString(
                    '+' . $master['phone'],
                    (isset(Yii::app()->session['isMobile']) ? Yii::app()->session['isMobile'] : false),
                    ImgLabelTypeEnum::TEXT
                );
            }

            if (!empty($addressCoord)) {
                $masters[$num]['distance'] = SearchService::getInstance()->getDistance(
                    $addressCoord,
                    $master['address_coord']
                );
            }
        }

        return $masters;
    }


    /**
     * Выдает массив салонов для отображения
     *
     * @param array $salons данные салонов
     * @param string $addressCoord координаты клиента
     *
     * @return array
     * @throws Exception
     */
    public function getFullArraySalonsToOrder(array $salons, $addressCoord = null)
    {
        foreach ($salons as $num => $salon) {
            $salons[$num]['avatarUrl'] = !empty($salon['small_logo_file_name'])
                ? IMG_SMALL_DIR . $salon['small_logo_file_name'] : IMG_PATH_NO_AVATAR;

            $salons[$num]['services_list'] = $this->getSalonServicesList($salon['id']);
            $salons[$num]['portfolio'] = PortfolioService::getInstance()->getPortfolio(
                $salon['user_id'],
                UserTypeEnum::SALON
            );

            $salons[$num]['fio'] = $salon['gendir_first_name'] . ' ' . $salon['gendir_last_name'] . ' ' . $salon['gendir_middle_name'];
            $salons[$num]['active'] = StatisticService::getInstance()->userActivity(
                null,
                $salon['last_hit_time']
            );

            $salons[$num]['img_class'] = ($salons[$num]['avatarUrl'] !== IMG_PATH_NO_AVATAR ? 'round_photo' : 'round_no_photo');
            if (!empty($salon['phone'])) {
                $salons[$num]['phoneImg'] = ImageHelper::createImageFromString(
                    '+' . $salon['phone'],
                    (isset(Yii::app()->session['isMobile']) ? Yii::app()->session['isMobile'] : false),
                    ImgLabelTypeEnum::TEXT
                );
            }

            if (!empty($addressCoord)) {
                $salons[$num]['distance'] = SearchService::getInstance()->getDistance(
                    $addressCoord,
                    $salon['address_coord']
                );
            }
        }

        return $salons;
    }

    /**
     * Станции метро Москвы
     *
     * @param string $site источник
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getSubways($site = 'superJob')
    {
        if ($site === 'superJob') {
            $sourceUrl = 'https://api.superjob.ru/2.0/suggest/town/4/metro/all/';
        } else {
            $sourceUrl = 'https://apidata.mos.ru/v1/datasets/1488/rows?$top=100&api_key=' . API_MOS_KEY;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $sourceUrl);
        // Pass TRUE or 1 if you want to wait for and catch the response against the request made
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // For Debug mode; shows up any error encountered during the operation
        curl_setopt($curl, CURLOPT_VERBOSE, 1);
        $response = curl_exec($curl);

        if (empty($response)) {
            BtLogger::getLogger()->error('Can not find subways.', [
                'site' => $site,
            ]);

            return [];
        }

        $responseJson = json_decode($response, true);
        if (empty($responseJson['objects'])) {
            BtLogger::getLogger()->error('Can not find subways.', [
                'site' => $site,
            ]);

            return [];
        }

        return $responseJson['objects'];
    }
}
