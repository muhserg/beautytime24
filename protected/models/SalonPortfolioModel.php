<?php

/**
 * Портфолио салона
 *
 */
class SalonPortfolioModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return SalonPortfolioModel
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
        return TableNameEnum::SALON_PORTFOLIO;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['work_photo_file_name, user_id', 'required'],
            ['about', 'length', 'max' => 2000],
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
