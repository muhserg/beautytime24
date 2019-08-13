<?php

/**
 * Сообщения пользователей
 *
 *
 */
class MessageModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return MessageModel
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
        return TableNameEnum::MESSAGE;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['from_user_id', 'required'],
            ['from_user_id', 'numerical', 'integerOnly' => true],
            ['title', 'length', 'max' => 255],
            ['message', 'length', 'max' => 2000],
            ['file', 'length', 'max' => 255],
            ['is_moderated', 'boolean'],
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
            'toUsers' => [self::BELONGS_TO, 'UsersMessageModel', ['id' => 'message_id']],
            'fromProfileClientData' => [self::BELONGS_TO, 'ClientProfileModel', ['from_user_id' => 'user_id']],
            'fromProfileMasterData' => [self::BELONGS_TO, 'MasterProfileModel', ['from_user_id' => 'user_id']],
            'fromProfileSalonData' => [self::BELONGS_TO, 'SalonProfileModel', ['from_user_id' => 'user_id']],
        ];
    }
}
