<?php

/**
 * Клик на страницу для статистики
 *
 *
 */
class HitModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return HitModel
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
        return TableNameEnum::HIT;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['session_db_id', 'required'],
            ['user_ip', 'length', 'max' => 40],
            ['uri', 'length', 'max' => 256],
            ['user_agent', 'length', 'max' => 2000],
            ['user_id, session_db_id', 'numerical', 'integerOnly' => true],
            [
                'created_at',
                'default',
                'value' => new \CDbExpression('NOW()'),
                'setOnEmpty' => true,
                'on' => 'insert',
            ],
        ];
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return [
            'session' => [self::BELONGS_TO, 'SessionModel', ['session_db_id' => 'id']],
        ];
    }
}
