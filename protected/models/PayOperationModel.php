<?php

/**
 * Платежные операции
 *
 */
class PayOperationModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return PayOperationModel
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
        return TableNameEnum::PAY_OPERATION;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['type_id, user_id, sum', 'required'],
            ['type_id, user_id', 'numerical', 'integerOnly' => true],
            ['sum', 'numerical', 'integerOnly' => false],
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
            'operationType' => [self::BELONGS_TO, 'PayOperationTypeModel', ['type_id' => 'id']],
        ];
    }
}
