<?php

/**
 * Типы пользователей
 *
 */
class UserTypeModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return UserTypeModel
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
        return TableNameEnum::USER_TYPE;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['name, rus_name', 'length', 'max' => 100],
        ];
    }
}
