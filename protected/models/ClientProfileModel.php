<?php

/**
 * Профиль клиента
 *
 */
class ClientProfileModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return ClientProfileModel
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
        return TableNameEnum::CLIENT_PROFILE;
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
            ['about', 'length', 'max' => 2000],
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
