<?php

/**
 * Профиль мастера
 *
 */
class MasterProfileModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return MasterProfileModel
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
        return TableNameEnum::MASTER_PROFILE;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['first_name, last_name, fio_address_hash, address, user_id', 'required'],
            ['first_name, last_name, middle_name, near_subway', 'length', 'max' => 100],
            ['place', 'length', 'max' => 50],
            ['phone', 'length', 'max' => 15],
            ['sms_confirm', 'boolean'],
            ['latitude, longitude', 'numerical', 'integerOnly' => false],
            ['user_id', 'numerical', 'integerOnly' => true],
            ['about', 'length', 'max' => 2000],
            ['is_vacancy', 'boolean'],
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
            'directions' => [self::HAS_MANY, 'MasterProfileDirectionModel', ['profile_id' => 'id']],
            'hit' => [self::HAS_MANY, 'HitModel', ['user_id' => 'user_id']],
            'schedule' => [self::HAS_MANY, 'WorkScheduleModel', ['profile_id' => 'id']],
            'notification' => [self::BELONGS_TO, 'NotifyModel', ['user_id' => 'user_id']],
        ];
    }
}
