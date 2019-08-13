<?php

/**
 * Расписание работы для вакансии мастера
 *
 */
class VacancyScheduleModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return VacancyScheduleModel
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return TableNameEnum::VACANCY_SCHEDULE;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['salon_user_id, vacancy_id, weekdays, time_begin, time_end', 'required'],
            ['weekdays', 'length', 'max' => 100],
            ['time_begin, time_end', 'length', 'max' => 5],
            ['salon_user_id, vacancy_id', 'numerical', 'integerOnly' => true],
            [
                'created_at, updated_at',
                'default',
                'value' => new \CDbExpression('NOW()'),
                'setOnEmpty' => true,
                'on' => 'insert',
            ],
            [
                'updated_at',
                'default',
                'value' => new \CDbExpression('NOW()'),
                'setOnEmpty' => false,
                'on' => 'update',
            ],
        ];
    }
}
