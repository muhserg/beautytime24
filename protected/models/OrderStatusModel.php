<?php

/**
 * Статусы заказов
 *
 */
class OrderStatusModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return OrderStatusModel
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
        return TableNameEnum::ORDER_STATUS;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['id', 'numerical', 'integerOnly' => true],
            ['name', 'length', 'max' => 100],
        ];
    }
}
