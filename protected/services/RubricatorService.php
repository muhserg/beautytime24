<?php

/**
 * Обеспечивает работы с профилями пользователя
 */
class RubricatorService
{
    private static $rubricatorService = null;

    /**
     * @return RubricatorService
     */
    public static function getInstance()
    {
        if (self::$rubricatorService === null) {
            self::$rubricatorService = new self();
        }
        return self::$rubricatorService;
    }

    /** var CDbConnection */
    private $db;


    public function __construct()
    {
        $this->db = Yii::app()->db;
    }

    /**
     * Возвращает главные услуги
     *
     * @return array
     */
    public function getMainDirections()
    {
        return [
            'Парикмахерские услуги',
            'Ногтевой сервис',
            'Наращивание ресниц',
            'Эпиляция',
            'Брови',
            'Кометология',
            'Татуаж',
        ];
    }

    /**
     * получает список направлений
     *
     * @param boolean $IsShowOnlyMainDirections показывать только основыне направления
     *
     * @return array|CDbDataReader
     * @throws PublicMessageException
     */
    public function getDirections($IsShowOnlyMainDirections = false)
    {
        try {
            if ($IsShowOnlyMainDirections === true) {
                $criteria = new CDbCriteria();
                $criteria->addInCondition('name', $this->getMainDirections());
                $criteria->order = 'id ASC';
                DirectionModel::model()->getDbCriteria()->mergeWith($criteria);
            } else {
                DirectionModel::model()->getDbCriteria()->mergeWith(['order' => 'id ASC']);
            }

            return DirectionModel::model()->getCommandBuilder()
                ->createFindCommand(
                    DirectionModel::model()->tableSchema,
                    DirectionModel::model()->getDbCriteria()
                )
                ->queryAll();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not open directions. Fatal error.', [
                'userId' => isset(Yii::app()->session['user']['id']) ? Yii::app()->session['user']['id'] : null,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список направлений. Ошибка в БД.');
        }
    }

    /**
     * получает список услуг
     *
     * @param string $orderBy сортировка
     *
     * @return array|CDbDataReader
     * @throws PublicMessageException
     */
    public function getServices($orderBy = 'name ASC')
    {
        try {
            ServiceModel::model()->getDbCriteria()->mergeWith(['order' => $orderBy]);
            return ServiceModel::model()->getCommandBuilder()
                ->createFindCommand(ServiceModel::model()->tableSchema, ServiceModel::model()->dbCriteria)
                ->queryAll();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not open services. Fatal error.', [
                'userId' => isset(Yii::app()->session['user']['id']) ? Yii::app()->session['user']['id'] : null,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список услуг. Ошибка в БД.');
        }
    }

    /**
     * Получаем выбранные направления и услуги для профиля пользователя
     *
     * @param string $userType тип пользователя
     * @param integer $profileId идентификатор профиля
     * @param array $profileFields массив полей профиля для отображения в twig
     * @return array
     * @throws PublicMessageException
     */
    public function getSelectedDirectionsAndServices($userType, $profileId, $profileFields)
    {
        $selectedServDirections = []; //направления, в которые выбраны в комбобоксе направлений

        if ($userType === UserTypeEnum::MASTER) {
            $selectedDirections = MasterProfileDirectionModel::model()->findAllByAttributes(['profile_id' => $profileId]);
            $selectedServices = MasterProfileServiceModel::model()->with('additional_services')->findAllByAttributes([
                'profile_id' => $profileId,
            ]);
        } elseif ($userType === UserTypeEnum::SALON) {
            $selectedDirections = SalonProfileDirectionModel::model()->findAllByAttributes(['profile_id' => $profileId]);
            $selectedServices = SalonProfileServiceModel::model()->with('additional_services')->findAllByAttributes([
                'profile_id' => $profileId
            ]);
        } else {
            throw new PublicMessageException(ErrorEnum::NOT_FOUND_FOR_USER_TYPE);
        }

        foreach ($profileFields['profileDirections'] as $num => $direction) {
            $profileFields['profileDirections'][$num]['selected'] = '';
            foreach ($selectedDirections as $selectedDirection) {
                if ($direction['id'] === $selectedDirection->direction_id) {
                    $profileFields['profileDirections'][$num]['selected'] = 'selected';
                }
            }
        }

        foreach ($profileFields['profileServices'] as $num => $service) {
            $profileFields['profileServices'][$num]['selected'] = '';
            foreach ($selectedServices as $selectedService) {
                if ($service['id'] === $selectedService->service_id) {
                    $selectedServDirections[$service['direction_id']] = 1;
                    $profileFields['profileServices'][$num]['selected'] = 'selected';
                    $profileFields['profileServices'][$num]['duration'] = $selectedService->duration;
                    $profileFields['profileServices'][$num]['cost'] = $selectedService->cost;

                    if (!empty($selectedService->additional_services)) {
                        $profileFields['profileServices'][$num]['additional_services'] = [];
                        if (is_array($selectedService->additional_services)) {
                            foreach ($selectedService->additional_services as $additionalSelectedService) {
                                $profileFields['profileServices'][$num]['additional_services'][] = [
                                    'id' => $additionalSelectedService->id,
                                    'name' => $additionalSelectedService->name,
                                    'duration' => $additionalSelectedService->duration,
                                    'cost' => $additionalSelectedService->cost,
                                ];
                            }
                        } else {
                            $profileFields['profileServices'][$num]['additional_services'][] = [
                                'id' => $selectedService->additional_services->id,
                                'name' => $selectedService->additional_services->name,
                                'duration' => $selectedService->additional_services->duration,
                                'cost' => $selectedService->additional_services->cost,
                            ];
                        }

                    }
                }
            }
        }

        foreach ($profileFields['profileServices'] as $num => $service2) {
            if (isset($selectedServDirections[$service2['direction_id']])) {
                $profileFields['profileServices'][$num]['selectedInDirection'] = 'selected';
            }
        }

        return $profileFields;
    }


    /**
     * Получаем выбранные направления для профиля пользователя
     *
     * @param string $userType тип пользователя
     * @param integer $profileId идентификатор профиля
     * @return array
     * @throws PublicMessageException
     */
    public function getSelectedDirections($userType, $profileId)
    {
        if ($userType === UserTypeEnum::MASTER) {
            $selectedDirections = MasterProfileDirectionModel::model()->findAllByAttributes(['profile_id' => $profileId]);
        } elseif ($userType === UserTypeEnum::SALON) {
            $selectedDirections = SalonProfileDirectionModel::model()->findAllByAttributes(['profile_id' => $profileId]);
        } else {
            throw new PublicMessageException(ErrorEnum::NOT_FOUND_FOR_USER_TYPE);
        }

        return $selectedDirections;
    }

    /**
     * Обновление направлений в профиле
     *
     * @param string $userType тип пользователя
     * @param integer $profileId идентификатор профиля
     * @param integer $userId
     * @param array $selectedDirections идентификаторы выбранных направлений
     * @return bool
     *
     * @throws PublicMessageException
     */
    public function updateDirections($userType, $profileId, $userId, $selectedDirections)
    {
        $updatedSelectedDirections = [];

        if ($userType === UserTypeEnum::MASTER) {
            if (is_array($selectedDirections) && !empty($selectedDirections)) {
                foreach ($selectedDirections as $selectedDirection) {
                    $masterProfileDirectionModel = MasterProfileDirectionModel::model()->findByAttributes([
                        'profile_id' => $profileId,
                        'direction_id' => $selectedDirection,
                    ]);
                    if ($masterProfileDirectionModel === null) {
                        $masterProfileDirectionModel = new MasterProfileDirectionModel;
                        $masterProfileDirectionModel->profile_id = $profileId;
                        $masterProfileDirectionModel->direction_id = $selectedDirection;
                    }
                    $masterProfileDirectionModel->user_id = $userId;

                    if (!$masterProfileDirectionModel->save()) {
                        BtLogger::getLogger()->error('Can not save direction in profile.', [
                            'selectedDirections' => $selectedDirections,
                            'error' => $masterProfileDirectionModel->getErrors(),
                        ]);

                        throw new PublicMessageException('Не удалось сохранить область деятельности в профиле.');
                    }

                    $updatedSelectedDirections[] = $selectedDirection;
                }
            }
            $criteria = new CDbCriteria;
            $criteria->addInCondition('profile_id', [$profileId]);
            if (count($updatedSelectedDirections) > 0) {
                $criteria->addNotInCondition('direction_id', $updatedSelectedDirections);
            }
            MasterProfileDirectionModel::model()->deleteAll($criteria);
        } elseif ($userType === UserTypeEnum::SALON) {
            if (is_array($selectedDirections) && !empty($selectedDirections)) {
                foreach ($selectedDirections as $selectedDirection) {
                    $salonProfileDirectionModel = SalonProfileDirectionModel::model()->findByAttributes([
                        'profile_id' => $profileId,
                        'direction_id' => $selectedDirection,
                    ]);
                    if ($salonProfileDirectionModel === null) {
                        $salonProfileDirectionModel = new SalonProfileDirectionModel;
                        $salonProfileDirectionModel->profile_id = $profileId;
                        $salonProfileDirectionModel->direction_id = $selectedDirection;
                    }
                    $salonProfileDirectionModel->user_id = $userId;

                    if (!$salonProfileDirectionModel->save()) {
                        BtLogger::getLogger()->error('Can not save direction in profile.', [
                            'selectedDirections' => $selectedDirections,
                            'error' => $salonProfileDirectionModel->getErrors(),
                        ]);

                        throw new PublicMessageException('Не удалось сохранить область деятельности в профиле.');
                    }

                    $updatedSelectedDirections[] = $selectedDirection;
                }
            }
            $criteria = new CDbCriteria;
            $criteria->addInCondition('profile_id', [$profileId]);
            if (count($updatedSelectedDirections) > 0) {
                $criteria->addNotInCondition('direction_id', $updatedSelectedDirections);
            }
            SalonProfileDirectionModel::model()->deleteAll($criteria);
        }

        return true;
    }

    /**
     * Обновление услуг в профиле
     *
     * @param string $userType тип пользователя
     * @param integer $profileId идентификатор профиля
     * @param integer $userId
     * @param array $selectedServices идентификаторы выбранных услуг
     * @return bool
     *
     * @throws PublicMessageException
     */
    public function updateServices($userType, $profileId, $userId, $selectedServices)
    {
        $updatedSelectedServices = [];

        if ($userType === UserTypeEnum::MASTER) {
            if (is_array($selectedServices) && !empty($selectedServices)) {
                foreach ($selectedServices as $selectedService) {
                    $masterProfileServiceModel = MasterProfileServiceModel::model()->findByAttributes([
                        'profile_id' => $profileId,
                        'service_id' => $selectedService,
                    ]);
                    if ($masterProfileServiceModel === null) {
                        $masterProfileServiceModel = new MasterProfileServiceModel;
                        $masterProfileServiceModel->profile_id = $profileId;
                        $masterProfileServiceModel->service_id = $selectedService;
                    }
                    $masterProfileServiceModel->user_id = $userId;

                    if (!$masterProfileServiceModel->save()) {
                        BtLogger::getLogger()->error('Can not save service in profile.', [
                            'selectedDirections' => $selectedServices,
                            'error' => $masterProfileServiceModel->getErrors(),
                        ]);

                        throw new PublicMessageException('Не удалось сохранить услугу в профиле.');
                    }

                    $updatedSelectedServices[] = $selectedService;
                }
            }
            $criteria = new CDbCriteria;
            $criteria->addInCondition('profile_id', [$profileId]);
            if (count($updatedSelectedServices) > 0) {
                $criteria->addNotInCondition('service_id', $updatedSelectedServices);
            }
            MasterProfileServiceModel::model()->deleteAll($criteria);
        } elseif ($userType === UserTypeEnum::SALON) {
            if (is_array($selectedServices) && !empty($selectedServices)) {
                foreach ($selectedServices as $selectedService) {
                    $salonProfileServiceModel = SalonProfileServiceModel::model()->findByAttributes([
                        'profile_id' => $profileId,
                        'service_id' => $selectedService,
                    ]);
                    if ($salonProfileServiceModel === null) {
                        $salonProfileServiceModel = new SalonProfileServiceModel;
                        $salonProfileServiceModel->profile_id = $profileId;
                        $salonProfileServiceModel->service_id = $selectedService;
                    }
                    $salonProfileServiceModel->user_id = $userId;

                    if (!$salonProfileServiceModel->save()) {
                        BtLogger::getLogger()->error('Can not save service in profile.', [
                            'selectedDirections' => $selectedServices,
                            'error' => $salonProfileServiceModel->getErrors(),
                        ]);

                        throw new PublicMessageException('Не удалось сохранить услугу в профиле.');
                    }

                    $updatedSelectedServices[] = $selectedService;
                }
            }
            $criteria = new CDbCriteria;
            $criteria->addInCondition('profile_id', [$profileId]);
            if (count($updatedSelectedServices) > 0) {
                $criteria->addNotInCondition('service_id', $updatedSelectedServices);
            }
            SalonProfileServiceModel::model()->deleteAll($criteria);
        }

        return true;
    }


    /**
     * Сохранение стоимости и длительности услуг в профиле
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     * @param array $selectedDuration значения длительности услуги
     * @param array $selectedCost значения цены услуги
     * @param array $additionalServices массив собственных услуг
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function saveServiceCost(
        $userId,
        $userType,
        array $selectedDuration,
        array $selectedCost,
        array $additionalServices
    )
    {
        $profileModel = ProfileService::getInstance()->getProfileModel(null, $userType, $userId);
        if ($profileModel === null) {
            throw new PublicMessageException('Профиль не найден.');
        }

        if ($userType === UserTypeEnum::MASTER) {
            $transaction = $this->db->beginTransaction();
            foreach ($selectedCost as $serviceId => $cost) {
                $masterProfileServiceModel = MasterProfileServiceModel::model()->findByAttributes([
                    'profile_id' => $profileModel->id,
                    'service_id' => $serviceId,
                ]);
                if ($masterProfileServiceModel === null) {
                    $masterProfileServiceModel = new MasterProfileServiceModel;
                    $masterProfileServiceModel->profile_id = $profileModel->id;
                    $masterProfileServiceModel->service_id = $serviceId;
                }
                $masterProfileServiceModel->user_id = $userId;
                $masterProfileServiceModel->duration = (isset($selectedDuration[$serviceId]) ? $selectedDuration[$serviceId] : null);
                $masterProfileServiceModel->cost =
                    (isset($selectedCost[$serviceId]) && $selectedCost[$serviceId] > 0 ? $selectedCost[$serviceId] : null);

                if (!$masterProfileServiceModel->save()) {
                    BtLogger::getLogger()->error('Can not save service duration and cost in profile.', [
                        'service_id' => $serviceId,
                        'error' => $masterProfileServiceModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Не удалось сохранить услугу в профиле.');
                }
            }

            $this->saveAdditionalMasterServiceCost($userId, $profileModel->id, $additionalServices);
            $transaction->commit();
        } elseif ($userType === UserTypeEnum::SALON) {
            $transaction = $this->db->beginTransaction();
            foreach ($selectedDuration as $serviceId => $duration) {
                $salonProfileServiceModel = SalonProfileServiceModel::model()->findByAttributes([
                    'profile_id' => $profileModel->id,
                    'service_id' => $serviceId,
                ]);
                if ($salonProfileServiceModel === null) {
                    $salonProfileServiceModel = new SalonProfileServiceModel;
                    $salonProfileServiceModel->profile_id = $profileModel->id;
                    $salonProfileServiceModel->service_id = $serviceId;
                }
                $salonProfileServiceModel->user_id = $userId;
                $salonProfileServiceModel->duration = (isset($duration) ? $duration : null);
                $salonProfileServiceModel->cost =
                    (isset($selectedCost[$serviceId]) ? $selectedCost[$serviceId] : null);

                if (!$salonProfileServiceModel->save()) {
                    BtLogger::getLogger()->error('Can not save service duration and cost in profile.', [
                        'service_id' => $serviceId,
                        'error' => $salonProfileServiceModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Не удалось сохранить услугу в профиле.');
                }
            }

            $this->saveAdditionalSalonServiceCost($userId, $profileModel->id, $additionalServices);
            $transaction->commit();
        }

        return true;
    }

    /**
     * Получение стоимости и длительности собственных услуг мастера в профиле
     *
     * @param string $profileId идентификатор профиля пользователя
     * @param array $parentServiceId идентификатор родительской услуги в общем справочнике услуг
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function getAdditionalMasterServiceCost($profileId, $parentServiceId)
    {
        return MasterAdditionalServiceModel::model()->findAllByAttributes([
            'profile_id' => $profileId,
            'parent_service_id' => $parentServiceId,
        ]);
    }

    /**
     * Сохранение стоимости и длительности собственных услуг мастера в профиле
     *
     * @param integer $userId идентификатор пользователя
     * @param string $profileId идентификатор профиля пользователя
     * @param array $additionalServices массив собственных услуг
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function saveAdditionalMasterServiceCost($userId, $profileId, array $additionalServices)
    {
        foreach ($additionalServices['newAdditionalService'] as $parentServiceId => $additionalService) {
            foreach ($additionalService as $serviceId => $addServData) {
                $masterAdditionalModel = new MasterAdditionalServiceModel();
                $masterAdditionalModel->profile_id = $profileId;
                $masterAdditionalModel->parent_service_id = $parentServiceId;
                $masterAdditionalModel->user_id = $userId;
                $masterAdditionalModel->name = $addServData['name'];
                $masterAdditionalModel->duration = $addServData['duration'];
                $masterAdditionalModel->cost = $addServData['cost'] === '' ? null : $addServData['cost'];

                if ($masterAdditionalModel->save() === false) {
                    BtLogger::getLogger()->error('Can not save service duration and cost in profile.', [
                        'addServData' => $addServData,
                        'error' => $masterAdditionalModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Не удалось сохранить собственную услугу в профиле.');
                }
            }
        }

        foreach ($additionalServices['updateAdditionalService'] as $parentServiceId => $additionalService2) {
            foreach ($additionalService2 as $serviceId => $addServData) {
                $masterAdditionalModel = MasterAdditionalServiceModel::model()->findByPk($serviceId);
                $masterAdditionalModel->profile_id = $profileId;
                $masterAdditionalModel->parent_service_id = $parentServiceId;
                $masterAdditionalModel->user_id = $userId;
                $masterAdditionalModel->name = $addServData['name'];
                $masterAdditionalModel->duration = $addServData['duration'];
                $masterAdditionalModel->cost = $addServData['cost'] === '' ? null : $addServData['cost'];

                if ($masterAdditionalModel->save() === false) {
                    BtLogger::getLogger()->error('Can not save service duration and cost in profile.', [
                        'addServData' => $addServData,
                        'error' => $masterAdditionalModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Не удалось сохранить собственную услугу в профиле.');
                }
            }
        }

        foreach ($additionalServices['delAdditionalService'] as $parentServiceId => $additionalService3) {
            foreach ($additionalService3 as $serviceId => $addServData) {
                $masterAdditionalModel = MasterAdditionalServiceModel::model()->findByPk($serviceId);
                if ($masterAdditionalModel->delete() === false) {
                    BtLogger::getLogger()->error('Can not save service duration and cost in profile.', [
                        'addServData' => $addServData,
                        'error' => $masterAdditionalModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Не удалось сохранить собственную услугу в профиле.');
                }
            }
        }

        return true;
    }


    /**
     * Сохранение стоимости и длительности собственных услуг салона в профиле
     *
     * @param integer $userId идентификатор пользователя
     * @param string $profileId идентификатор профиля пользователя
     * @param array $additionalServices массив собственных услуг
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function saveAdditionalSalonServiceCost($userId, $profileId, array $additionalServices)
    {
        foreach ($additionalServices['newAdditionalService'] as $parentServiceId => $additionalService) {
            foreach ($additionalService as $serviceId => $addServData) {
                $salonAdditionalModel = new SalonAdditionalServiceModel();
                $salonAdditionalModel->profile_id = $profileId;
                $salonAdditionalModel->parent_service_id = $parentServiceId;
                $salonAdditionalModel->user_id = $userId;
                $salonAdditionalModel->name = $addServData['name'];
                $salonAdditionalModel->duration = $addServData['duration'];
                $salonAdditionalModel->cost = !empty($addServData['cost']) ? $addServData['cost'] : null;

                if ($salonAdditionalModel->save() === false) {
                    BtLogger::getLogger()->error('Can not save service duration and cost in profile.', [
                        'addServData' => $addServData,
                        'error' => $salonAdditionalModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Не удалось сохранить собственную услугу в профиле.');
                }
            }
        }

        foreach ($additionalServices['updateAdditionalService'] as $parentServiceId => $additionalService) {
            foreach ($additionalService as $serviceId => $addServData) {
                $salonAdditionalModel = SalonAdditionalServiceModel::model()->findByPk($serviceId);
                $salonAdditionalModel->profile_id = $profileId;
                $salonAdditionalModel->parent_service_id = $parentServiceId;
                $salonAdditionalModel->user_id = $userId;
                $salonAdditionalModel->name = $addServData['name'];
                $salonAdditionalModel->duration = $addServData['duration'];
                $salonAdditionalModel->cost = $addServData['cost'];

                if ($salonAdditionalModel->save() === false) {
                    BtLogger::getLogger()->error('Can not save service duration and cost in profile.', [
                        'addServData' => $addServData,
                        'error' => $salonAdditionalModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Не удалось сохранить собственную услугу в профиле.');
                }
            }
        }

        foreach ($additionalServices['delAdditionalService'] as $parentServiceId => $additionalService) {
            foreach ($additionalService as $serviceId => $addServData) {
                $salonAdditionalModel = SalonAdditionalServiceModel::model()->findByPk($serviceId);
                if ($salonAdditionalModel->delete() === false) {
                    BtLogger::getLogger()->error('Can not save service duration and cost in profile.', [
                        'addServData' => $addServData,
                        'error' => $salonAdditionalModel->getErrors(),
                    ]);

                    throw new PublicMessageException('Не удалось сохранить собственную услугу в профиле.');
                }
            }
        }

        return true;
    }


    /**
     * Преобразование стоимости и длительности собстеввных услуг в общий массив для обновления БД
     *
     * @param array $arPost POST массив полей
     *
     * @return array
     */
    public function parsingAdditionServicePostFields($arPost)
    {
        $newAdditionalService = [];
        $updateAdditionalService = [];
        $delAdditionalService = [];

        foreach ($arPost as $key => $value) {
            //ищем поле с названием собственной услуги
            if (strpos($key, 'additional-service-name-') !== false) {
                $matches = [];
                preg_match('/^additional-service-name-(\d+)-(.*)$/', $key, $matches);
                if (empty($matches[1]) || empty($matches[2])) {
                    continue;
                }
                $parentServiceId = $matches[1];
                $serviceId = $matches[2];

                //новые собственные услуги с пустым именем не сохраняем
                if (empty($value) && (strlen(IntVal($serviceId)) !== strlen($serviceId))) {
                    continue;
                }
                //старые собственные услуги с пустым именем удаляем
                if (empty($value) && (strlen(IntVal($serviceId)) == strlen($serviceId))) {
                    if (!isset($delAdditionalService[$parentServiceId])) {
                        $delAdditionalService[$parentServiceId] = [];
                    }
                    $delAdditionalService[$parentServiceId][$serviceId] = 1;
                    continue;
                }

                //старые собственные услуги с непустым именем обновляем
                if (strlen(IntVal($serviceId)) === strlen($serviceId)) {
                    if (!isset($updateAdditionalService[$parentServiceId])) {
                        $updateAdditionalService[$parentServiceId] = [];
                    }
                    $updateAdditionalService[$parentServiceId][$serviceId]['name'] = $value;
                    $updateAdditionalService[$parentServiceId][$serviceId]['duration'] =
                        !empty($_POST['additional-service-duration-' . $parentServiceId . '-' . $serviceId])
                            ? $_POST['additional-service-duration-' . $parentServiceId . '-' . $serviceId] : null;
                    $updateAdditionalService[$parentServiceId][$serviceId]['cost'] =
                        $_POST['additional-service-cost-' . $parentServiceId . '-' . $serviceId];
                } //новые собственные услуги с непустым именем добавляем
                else {
                    if (!isset($newAdditionalService[$parentServiceId])) {
                        $newAdditionalService[$parentServiceId] = [];
                    }
                    $newAdditionalService[$parentServiceId][$serviceId]['name'] = $value;
                    $newAdditionalService[$parentServiceId][$serviceId]['duration'] =
                        !empty($_POST['additional-service-duration-' . $parentServiceId . '-' . $serviceId])
                            ? $_POST['additional-service-duration-' . $parentServiceId . '-' . $serviceId] : null;
                    $newAdditionalService[$parentServiceId][$serviceId]['cost'] =
                        $_POST['additional-service-cost-' . $parentServiceId . '-' . $serviceId];
                }
            }
        }

        return [
            'newAdditionalService' => $newAdditionalService,
            'updateAdditionalService' => $updateAdditionalService,
            'delAdditionalService' => $delAdditionalService,
        ];
    }
}
