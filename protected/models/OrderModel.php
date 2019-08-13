<?php

/**
 * Заказы клиентов
 *
 */
class OrderModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return OrderModel
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
        return TableNameEnum::ORDER;
    }

    public function getRusPlace()
    {
        $rusPlace = [
            'my-house' => 'Выезд',
            'master-house' => 'У мастера',
            'all-house' => 'Везде'
        ];

        return ($this->place === null ? '' : $rusPlace[$this->place]);
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['main_service_id, owner_user_id, owner_user_type_id, total', 'required'],
            [
                'owner_user_id, owner_user_type_id, master_profile_id, salon_profile_id, rating',
                'numerical',
                'integerOnly' => true,
            ],
            ['latitude, longitude', 'numerical', 'integerOnly' => false],
            ['near_subway', 'length', 'max' => 100],
            ['photo_file_name', 'length', 'max' => 40],
            ['send_sms_order_create_flag', 'boolean'],
            ['address, address_coord', 'length', 'max' => 300],
            ['place', 'length', 'max' => 50],
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
            'salon' => [self::BELONGS_TO, 'SalonProfileModel', ['salon_profile_id' => 'id']],
            'clientOfsalon' => [self::BELONGS_TO, 'SalonProfileModel', ['owner_user_id' => 'user_id']],
            'client' => [self::BELONGS_TO, 'ClientProfileModel', ['owner_user_id' => 'user_id']],
            'rusStatus' => [self::BELONGS_TO, 'OrderStatusModel', ['status' => 'id']],
            'userType' => [self::BELONGS_TO, 'UserTypeModel', ['owner_user_type_id' => 'id']],
            'ownerUser' => [self::BELONGS_TO, 'UserModel', ['owner_user_id' => 'id']],
            'agreeMaster' => [self::HAS_MANY, 'BufMasterOrderModel', ['order_id' => 'id']],
            'agreeSalon' => [self::HAS_MANY, 'BufSalonOrderModel', ['order_id' => 'id']],
        ];
    }
}
