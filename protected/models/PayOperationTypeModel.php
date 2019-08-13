<?php

/**
 * Типы платежных операций
 *
 */
class PayOperationTypeModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return PayOperationTypeModel
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
        return TableNameEnum::PAY_OPERATION_TYPE;
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
