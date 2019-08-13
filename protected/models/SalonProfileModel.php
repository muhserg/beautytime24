<?php

/**
 * Профиль салона
 *
 */
class SalonProfileModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return SalonProfileModel
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
        return TableNameEnum::SALON_PROFILE;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['name, address, user_id', 'required'],
            ['inn', 'length', 'min' => 10, 'max' => 12],
            ['name_address_hash', 'length', 'max' => 32],
            ['phone', 'length', 'max' => 15],
            ['place', 'length', 'max' => 50],
            ['small_logo_file_name', 'length', 'max' => 40],
            ['sms_confirm', 'boolean'],
            ['gendir_first_name, gendir_last_name, gendir_middle_name, near_subway', 'length', 'max' => 100],
            ['latitude, longitude', 'numerical', 'integerOnly' => false],
            ['description', 'length', 'max' => 2000],
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
