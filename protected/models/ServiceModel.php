<?php

/**
 * Справочник услуг, сгруппированных по направлению
 *
 */
class ServiceModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return ServiceModel
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
        return TableNameEnum::SERVICE;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['name', 'required'],
            ['name, end_name', 'length', 'max' => 250],
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
