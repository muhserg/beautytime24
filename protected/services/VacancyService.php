<?php

/**
 * Сервис для обработки вакансий мастеров
 */
class VacancyService
{
    private static $vacancyService = null;

    /** var DateFormatter */
    private $dateFormatter;

    /** @var \CDbConnection */
    private $db;

    /**
     * @return VacancyService
     */
    public static function getInstance()
    {
        if (self::$vacancyService === null) {
            self::$vacancyService = new self();
        }
        return self::$vacancyService;
    }

    public function __construct()
    {
        $this->db = Yii::app()->db;
        $this->dateFormatter = new DateFormatter();
    }


    /**
     * Создание вакансии мастера
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $professionId идентификатор профессии (эквивалентно направлению)
     * @param string $workExperience стаж работы
     * @param string $scheduleWeekday график работы - дни недели
     * @param string $timeScheduleBegin график работы - время начала
     * @param string $timeScheduleEnd график работы - время окончания
     * @param string $about описание вакансии
     * @param string $salary зарплата
     * @param string $phone телефон вакансии
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function create(
        $userId,
        $professionId,
        $workExperience,
        $scheduleWeekday,
        $timeScheduleBegin,
        $timeScheduleEnd,
        $about,
        $salary,
        $phone
    ) {
        try {
            if (empty($userId) || empty($professionId) || empty($about)) {
                throw new PublicMessageException('Отсутствуют входные параметры.');
            }

            $salonModel = ProfileService::getInstance()->getProfileModel(null, UserTypeEnum::SALON, $userId);
            if ($salonModel === null) {
                throw new PublicMessageException('Для создания вакансии необходимо заполнить профиль.');
            }

            $vacancyModel = new VacancyModel;
            $vacancyModel->salon_user_id = $userId;
            $vacancyModel->profession_id = $professionId;
            $vacancyModel->work_experience = $workExperience;
            $vacancyModel->description = $about;
            $vacancyModel->salary = $salary;
            $vacancyModel->phone = (new PhoneHelper)->formatPhoneForSave($phone);

            if (!empty($salonModel->photo_inside_filename)) {
                $vacancyModel->work_place_photo = $salonModel->photo_inside_filename;
            }
            $vacancyModel->rating = $salonModel->rating;

            $transaction = $this->db->beginTransaction();
            if (!$vacancyModel->save()) {
                BtLogger::getLogger()->error('Can not create vacancy.', [
                    'userId' => $userId,
                    'profession_id' => $professionId,
                    'error' => $vacancyModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось создать вакансию.');
            }

            $this->saveSchedule(
                $userId,
                $vacancyModel->id, [
                'weekdays' => $scheduleWeekday,
                'timeBegin' => $timeScheduleBegin,
                'timeEnd' => $timeScheduleEnd,
            ]);
            $transaction->commit();

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not create vacancy. Fatal error.', [
                'userId' => $userId,
                'profession_id' => $professionId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось создать вакансию. Ошибка в БД.');
        }
    }

    /**
     * Привязка мастера к вакансии мастера
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $orderId идентификатор заказа
     * @param integer $masterProfileId идентификатор мастера
     *
     * @return string
     * @throws PublicMessageException
     */
    public function bindMaster(
        $userId,
        $orderId,
        $masterProfileId
    ) {
        try {
            $orderModel = OrderModel::model()->findByPk($orderId);
            if ($orderModel->owner_user_id !== $userId) {
                throw new PublicMessageException('Заказ не принадлежит данному пользователю.');
            }
            $masterModel = MasterProfileModel::model()->findByPk($masterProfileId);
            if (empty($masterModel)) {
                throw new PublicMessageException('Мастер не найден.');
            }

            $orderModel->master_profile_id = $masterProfileId;

            if (!$orderModel->save()) {
                BtLogger::getLogger()->error('Can not bind master to order.', [
                    'userId' => $userId,
                    'orderId' => $orderId,
                    'masterId' => $masterProfileId,
                    'error' => $orderModel->getErrors(),
                ]);

                throw new PublicMessageException('Ошибка выбора мастера для заказа.');
            }

            return $masterModel->last_name . ' ' . $masterModel->first_name;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not bind master to order. Fatal error.', [
                'userId' => $userId,
                'orderId' => $orderId,
                'masterId' => $masterProfileId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Ошибка выбора мастера для заказа. Ошибка в БД.');
        }
    }

    /**
     * Список вакансий салона
     *
     * @param integer $userId идентификатор пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getMyVacancies($userId)
    {
        try {
            $vacancies = VacancyModel::model()->with('direction')->findAllByAttributes(['salon_user_id' => $userId]);

            foreach ($vacancies as $num => $vacancy) {
                $vacancy->salary = ($vacancy->salary !== null ? $vacancy->salary : '');
                $vacancy->phone = ($vacancy->phone !== null ? (new PhoneHelper)->formatPhoneForView($vacancy->phone) : '');
            }

            return $vacancies;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get my vacancies. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список Ваших вакансий. Ошибка в БД.');
        }
    }

    /**
     * Все вакансии для мастеров
     *
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getAllVacancies()
    {
        try {
            $vacancies = VacancyModel::model()->with('direction')->findAll();

            foreach ($vacancies as $num => $vacancy) {
                $vacancy->salary = ($vacancy->salary !== null ? $vacancy->salary : '');
                $vacancy->phone = ($vacancy->phone !== null ? (new PhoneHelper)->formatPhoneForView($vacancy->phone) : '');
            }

            return $vacancies;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get All vacancies. Fatal error.', [
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список вакансий. Ошибка в БД.');
        }
    }

    /**
     * Все вакансии для мастеров по соответствующему направлению
     *
     * @param array $directions массив моделей направления деятельности
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getAllVacanciesByDirection(array $directionModels)
    {
        $directionIds = [];
        foreach ($directionModels as $directionModel) {
            $directionIds[] = $directionModel->direction_id;
        }

        try {
            $vacancies = VacancyModel::model()->with('direction')->findAllByAttributes([], [
                'condition' => 'profession_id IN (' . join(',', $directionIds) . ')',
            ]);

            foreach ($vacancies as $num => $vacancy) {
                $vacancy->salary = ($vacancy->salary !== null ? $vacancy->salary : '');
                $vacancy->phone = ($vacancy->phone !== null ? (new PhoneHelper)->formatPhoneForView($vacancy->phone) : '');
            }

            return $vacancies;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get All vacancies. Fatal error.', [
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список вакансий. Ошибка в БД.');
        }
    }

    /**
     * Число вакансий
     *
     * @return integer
     * @throws PublicMessageException
     */
    public function getCounts()
    {
        try {
            return (new VacancyDsp)->getCounts();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get Vacancy. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить число выкансий. Ошибка в БД.');
        }
    }

    /**
     * Список случайных $count вакансий
     *
     * @param integer $count число вакансий
     *
     * @return array
     * @throws PublicMessageException
     */
    public function randVacancies($count)
    {
        try {
            $vacancies = VacancyModel::model()->with('direction')->findAllByAttributes([], [
                'order' => 'rand()',
                'limit' => $count,
            ]);

            foreach ($vacancies as $num => $vacancy) {
                $vacancy->salary = ($vacancy->salary !== null ? $vacancy->salary : '');
            }

            return $vacancies;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get vacancies. Fatal error.', [
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить случайных вакансий. Ошибка в БД.');
        }
    }

    /**
     * Вакансия по Id
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $vacancyId номер вакансии
     *
     * @return VacancyModel
     * @throws PublicMessageException
     */
    public function getById($userId, $vacancyId)
    {
        try {
            $vacancyModel = VacancyModel::model()->with('direction')->findByAttributes([
                'salon_user_id' => $userId,
                'id' => $vacancyId,
            ]);

            return $vacancyModel;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get vacancy. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить вакансию. Ошибка в БД.');
        }
    }

    /**
     * Сохранение расписания работы для вакансии
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $vacancyId идентификатор вакансии
     * @param array $scheduleSettings параметры времени работы
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function saveSchedule($userId, $vacancyId, $scheduleSettings)
    {
        if (empty($scheduleSettings)) {
            throw new PublicMessageException('Отсутствуют входные параметры.');
        }
        if (empty($scheduleSettings['weekdays'])) {
            throw new PublicMessageException('Отсутствуют дни недели.');
        }

        try {
            //получаем модель
            $vacancyScheduleModel = VacancyScheduleModel::model()->findByAttributes(['vacancy_id' => $vacancyId]);
            if ($vacancyScheduleModel === null) {
                $vacancyScheduleModel = new VacancyScheduleModel;
                if ($vacancyScheduleModel === null) {
                    throw new PublicMessageException('Не удалось создать расписание для вакансии.');
                }
            }

            //заполняем модель
            $vacancyScheduleModel->salon_user_id = $userId;
            $vacancyScheduleModel->vacancy_id = $vacancyId;
            $vacancyScheduleModel->weekdays = join(',', $scheduleSettings['weekdays']);
            $vacancyScheduleModel->time_begin = $scheduleSettings['timeBegin'];
            $vacancyScheduleModel->time_end = $scheduleSettings['timeEnd'];

            if (!$vacancyScheduleModel->save()) {
                BtLogger::getLogger()->error('Can not save vacancy schedule.', [
                    'scheduleSettings' => $scheduleSettings,
                    'error' => $vacancyScheduleModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось сохранить график работы вакансии.');
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save vacancy schedule. Fatal error.', [
                'scheduleSettings' => $scheduleSettings,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось сохранить график работы вакансии. Ошибка в БД.');
        }
    }
}
