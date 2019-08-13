<?php

/**
 * Предварительные заказы клиентов
 *
 */
class BufOrderModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return BufOrderModel
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
        return TableNameEnum::BUF_ORDER;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['main_service_id, owner_session_php_id, total', 'required'],
            [
                'salon_profile_id, rating',
                'numerical',
                'integerOnly' => true,
            ],
            ['latitude, longitude', 'numerical', 'integerOnly' => false],
            ['near_subway', 'length', 'max' => 100],
            ['photo_file_name', 'length', 'max' => 40],
            ['owner_session_php_id', 'length', 'max' => 26],
            ['address, address_coord', 'length', 'max' => 300],
            ['description', 'length', 'max' => 2000],
            ['total, plan_price', 'numerical', 'integerOnly' => false],
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
            'service' => [self::BELONGS_TO, 'ServiceModel', ['main_service_id' => 'id']],
            'master' => [self::BELONGS_TO, 'MasterProfileModel', ['master_profile_id' => 'id']],
            'client' => [self::BELONGS_TO, 'ClientProfileModel', ['owner_user_id' => 'user_id']],
        ];
    }
}
