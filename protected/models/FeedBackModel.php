<?php

/**
 * Учетные записи пользователей
 *
 * @property integer $id Идентификатор записи
 * @property string $Name полное имя пользователя
 * @property integer $type тип пользователя
 *
 */
class FeedBackModel extends CActiveRecord
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
        return TableNameEnum::FEEDBACK;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['sender_name, comment', 'required'],
            ['sender_name', 'length', 'max' => 250],
            ['phone, email', 'length', 'max' => 100],
            ['user_id', 'numerical', 'integerOnly' => true],
            ['comment', 'length', 'max' => 2000],
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
            'user' => [self::BELONGS_TO, 'UserModel', ['user_id' => 'id']],
        ];
    }
}
