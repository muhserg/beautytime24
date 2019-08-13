<?php

/**
 * Настройки уведомлений
 *
 *
 */
class NotifyModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return NotifyModel
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
        return TableNameEnum::NOTIFY;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['user_id, type', 'required'],
            ['user_id', 'numerical', 'integerOnly' => true],
            ['type', 'length', 'max' => 15],
            ['radius', 'numerical', 'integerOnly' => false],
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
