<?php

/**
 * Обеспечивает работу с портфолио пользователя
 */
class ScheduleService
{
    private static $scheduleService = null;

    /**
     * @return ScheduleService
     */
    public static function getInstance()
    {
        if (self::$scheduleService === null) {
            self::$scheduleService = new self();
        }
        return self::$scheduleService;
    }


    public function getWeekDaysBase()
    {
        return [
            ['id' => 1, 'name' => 'Понедельник'],
            ['id' => 2, 'name' => 'Вторник'],
            ['id' => 3, 'name' => 'Среда'],
            ['id' => 4, 'name' => 'Четверг'],
            ['id' => 5, 'name' => 'Пятница'],
            ['id' => 6, 'name' => 'Суббота'],
            ['id' => 7, 'name' => 'Воскресенье'],
        ];
    }

    /**
     * Возвращает расписания работы мастера или салона
     *
     * @param integer $userId идентификатор пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function get($userId)
    {
        $workSchedules = [];

        try {
            $workScheduleModels = WorkScheduleModel::model()->findAllByAttributes(['user_id' => $userId]);
            if (empty($workScheduleModels)) {
                return [
                    [
                        'scheduleId' => null,
                        'weekdays' => $this->getWeekDaysBase(),
                        'timeBegin' => null,
                        'timeEnd' => null,
                    ],
                ];
            }

            foreach ($workScheduleModels as $workScheduleModel) {
                $weekdays = $this->getSelected(explode(',', $workScheduleModel->weekdays));

                $workSchedules[] = [
                    'scheduleId' => $workScheduleModel->id,
                    'weekdays' => $weekdays,
                    'timeBegin' => $workScheduleModel->time_begin,
                    'timeEnd' => $workScheduleModel->time_end,
                ];
            }

            return $workSchedules;
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get work schedule. Fatal error.', [
                'workSchedules' => $workSchedules,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить рабочее время. Ошибка в БД.');
        }
    }

    /**
     * Формирование массива выбранных дней недели в расписании
     *
     * @param array $weekdaySelects массив выбранных дней
     * @return array
     *
     */
    public function getSelected($weekdaySelects)
    {
        $weekdays = $this->getWeekDaysBase();
        foreach ($weekdays as $num => $weekday) {
            if (in_array(strval($weekday['id']), $weekdaySelects, true)) {
                $weekdays[$num]['selected'] = 'selected';
            }
        }

        return $weekdays;
    }


    /**
     * Сохранение расписания работы мастера и салона
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $userType тип пользователя
     * @param array $scheduleSettings параметры времени работы
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function save($userId, $userType, $scheduleSettings)
    {
        if (empty($scheduleSettings)) {
            throw new PublicMessageException('Отсутствуют входные параметры.');
        }

        if ($userType === UserTypeEnum::CLIENT) {
            throw new PublicMessageException('Для данного пользователя нельзя задать время работы.');
        }

        try {
            //получаем модель
            $workScheduleModel = WorkScheduleModel::model()->findByPk($scheduleSettings['scheduleId']);
            if ($workScheduleModel === null) {
                $workScheduleModel = new WorkScheduleModel;
                if ($workScheduleModel === null) {
                    throw new PublicMessageException('Не удалось создать модель расписания.');
                }
            }

            //заполняем модель
            $workScheduleModel->user_id = $userId;
            $profileModel = ProfileService::getInstance()->getProfileModel(null, $userType, $userId);
            $workScheduleModel->profile_id = $profileModel->id;

            //дни недели не учитываем
            $workScheduleModel->weekdays = join(',', [1, 2, 3, 4, 5, 6, 7]);
            $workScheduleModel->time_begin = $scheduleSettings['timeBegin'];
            $workScheduleModel->time_end = $scheduleSettings['timeEnd'];

            if (!$workScheduleModel->save()) {
                BtLogger::getLogger()->error('Can not save schedule.', [
                    'scheduleSettings' => $scheduleSettings,
                    'error' => $workScheduleModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось сохранить время работы.');
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save schedule. Fatal error.', [
                'scheduleSettings' => $scheduleSettings,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось сохранить время работы. Ошибка в БД.');
        }
    }
}
