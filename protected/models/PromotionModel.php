<?php

/**
 * Акции пользователей
 *
 */
class PromotionModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return PromotionModel
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
        return TableNameEnum::PROMOTION;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['owner_user_id, date_begin, title, text', 'required'],
            ['title, url, image_url', 'length', 'max' => 250],
            ['address', 'length', 'max' => 300],
            ['discount, latitude, longitude', 'numerical', 'integerOnly' => false],
            ['discount_type', 'length', 'max' => 30],
            ['near_subway', 'length', 'max' => 100],
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
            'user' => [self::BELONGS_TO, 'UserModel', ['owner_user_id' => 'id']],
        ];
    }
}
