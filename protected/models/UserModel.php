<?php

/**
 * Учетные записи пользователей
 *
 * @property integer $id Идентификатор записи
 * @property string $Name полное имя пользователя
 * @property integer $type тип пользователя
 *
 */
class UserModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return UserModel
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
        return TableNameEnum::USER;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['login, pass', 'required'],
            ['login', 'length', 'max' => 250],
            ['pass', 'length', 'max' => 32],
            ['admin_flag, sms_pay_flag', 'boolean'],
            ['type', 'numerical', 'integerOnly' => true],
            ['balance', 'numerical', 'integerOnly' => false],
            ['phone, email', 'length', 'max' => 100],
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
            'userType' => [self::BELONGS_TO, 'UserTypeModel', ['type' => 'id']],
        ];
    }
}
