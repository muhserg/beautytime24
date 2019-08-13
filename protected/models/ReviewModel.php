<?php

/**
 * Отзывы пользователей
 *
 *
 */
class ReviewModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return ReviewModel
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
        return TableNameEnum::REVIEW;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['user_id', 'required'],
            ['user_id, order_id, assessment', 'numerical', 'integerOnly' => true],
            ['text', 'length', 'max' => 2000],
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
            'clientProfile' => [self::BELONGS_TO, 'ClientProfileModel', ['user_id' => 'user_id']],
            'order' => [self::BELONGS_TO, 'OrderModel', ['order_id' => 'id']],
        ];
    }
}
