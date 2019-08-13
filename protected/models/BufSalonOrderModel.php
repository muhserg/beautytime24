<?php

/**
 * Несогласованные обоими сторонами салоны к заказу
 *
 */
class BufSalonOrderModel extends CActiveRecord
{
    /**
     * @param string $className
     * @return BufSalonOrderModel
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
        return TableNameEnum::BUF_SALON_ORDER;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['profile_id, order_id', 'required'],
            ['profile_id, order_id', 'numerical', 'integerOnly' => true],
            ['client_agree, salon_agree', 'boolean'],
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
        return ['profile_id', 'order_id'];
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return [
            'salonProfile' => [self::BELONGS_TO, 'SalonProfileModel', ['profile_id' => 'id']],
        ];
    }
}
