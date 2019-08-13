<?php

/**
 * Выбранные услуги в профиле мастера
 *
 */
class MasterProfileServiceModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return MasterProfileServiceModel
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
        return TableNameEnum::MASTER_PROFILE_SERVICE;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['profile_id, service_id, user_id', 'required'],
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

    public function primaryKey()
    {
        return ['profile_id', 'service_id'];
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return [
            'service' => [self::BELONGS_TO, 'ServiceModel', ['service_id' => 'id']],
            'additional_services' => [
                self::HAS_MANY,
                'MasterAdditionalServiceModel', [
                    'parent_service_id' => 'service_id',
                    'profile_id' => 'profile_id',
                ]
            ],
        ];
    }
}
