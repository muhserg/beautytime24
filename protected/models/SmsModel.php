<?php

/**
 * СМС отправленные пользователям
 *
 * @property integer $id Идентификатор записи
 * @property string $Name полное имя пользователя
 * @property integer $type тип пользователя
 *
 */
class SmsModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return SmsModel
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
        return TableNameEnum::SMS;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['phone, message, type', 'required'],
            ['phone, type', 'length', 'max' => 30],
            ['message', 'length', 'max' => 500],
            ['response', 'length', 'max' => 2000],
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
