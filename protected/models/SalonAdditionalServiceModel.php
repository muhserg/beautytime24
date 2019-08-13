<?php

/**
 * Собственные услуги в профиле салона, которые введены самим салоном
 *
 */
class SalonAdditionalServiceModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return SalonAdditionalServiceModel
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
        return TableNameEnum::SALON_ADDITIONAL_SERVICE;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['profile_id, parent_service_id, user_id, name', 'required'],
            ['name', 'length', 'max' => 255],
            ['user_id', 'numerical', 'integerOnly' => true],
            ['duration, cost', 'numerical', 'integerOnly' => false],
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
            'service' => [self::BELONGS_TO, 'ServiceModel', ['parent_service_id' => 'id']],
        ];
    }
}
