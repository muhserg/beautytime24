<?php

/**
 * Выбранные направления в профиле салона
 *
 */
class SalonProfileDirectionModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return SalonProfileDirectionModel
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
        return TableNameEnum::SALON_PROFILE_DIRECTION;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['profile_id, direction_id, user_id', 'required'],
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
        return ['profile_id', 'direction_id'];
    }
}
