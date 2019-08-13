<?php

/**
 * php сессии для статистики
 *
 *
 */
class SessionModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return SessionModel
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
        return TableNameEnum::SESSION;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['session_php_id', 'required'],
            ['session_php_id', 'length', 'max' => 26],
            ['uri_begin', 'length', 'max' => 256],
            ['user_agent', 'length', 'max' => 2000],
            ['user_id', 'numerical', 'integerOnly' => true],
            [
                'created_at',
                'default',
                'value' => new \CDbExpression('NOW()'),
                'setOnEmpty' => true,
                'on' => 'insert',
            ],
        ];
    }
}
