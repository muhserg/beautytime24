<?php

/**
 * Получатели сообщений пользователя
 *
 *
 */
class UsersMessageModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return UsersMessageModel
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
        return TableNameEnum::USERS_MESSAGE;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['from_user_id, message_id, to_user_type_name', 'required'],
            ['from_user_id, to_user_id, message_id', 'numerical', 'integerOnly' => true],
            ['to_user_type_name', 'length', 'max' => 100],
        ];
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return [
            'message' => [self::BELONGS_TO, 'MessageModel', ['message_id' => 'id']],
            'toUserData' => [self::BELONGS_TO, 'UserModel', ['to_user_id' => 'id']],

            'fromProfileClientData' => [self::BELONGS_TO, 'ClientProfileModel', ['from_user_id' => 'user_id']],
            'fromProfileMasterData' => [self::BELONGS_TO, 'MasterProfileModel', ['from_user_id' => 'user_id']],
            'fromProfileSalonData' => [self::BELONGS_TO, 'SalonProfileModel', ['from_user_id' => 'user_id']],

            'toProfileClientData' => [self::BELONGS_TO, 'ClientProfileModel', ['to_user_id' => 'user_id']],
            'toProfileMasterData' => [self::BELONGS_TO, 'MasterProfileModel', ['to_user_id' => 'user_id']],
            'toProfileSalonData' => [self::BELONGS_TO, 'SalonProfileModel', ['to_user_id' => 'user_id']],
        ];
    }
}
