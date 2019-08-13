<?php

/**
 * Видео сслыки для обучения мастеров
 *
 */
class StudyVideoModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return StudyVideoModel
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
        return TableNameEnum::STUDY_VIDEO;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['title, link, description, owner_user_id, direction_id', 'required'],
            ['owner_user_id, direction_id', 'numerical', 'integerOnly' => false],
            ['title', 'length', 'max' => 255],
            ['link', 'length', 'max' => 500],
            ['description', 'length', 'max' => 255],
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
            'direction' => [self::BELONGS_TO, 'DirectionModel', ['direction_id' => 'id']],
        ];
    }
}
