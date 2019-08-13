<?php

/**
 * Вакансия мастера, созданная салоном
 *
 */
class VacancyModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return VacancyModel
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
        return TableNameEnum::VACANCY;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['profession_id, salon_user_id, work_experience', 'required'],
            ['work_place_photo', 'length', 'max' => 40],
            ['description', 'length', 'max' => 2000],
            ['salon_user_id, profession_id, work_experience', 'numerical', 'integerOnly' => true],
            ['salary, rating', 'numerical', 'integerOnly' => false],
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

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return [
            'direction' => [self::BELONGS_TO, 'DirectionModel', ['profession_id' => 'id']],
        ];
    }
}
