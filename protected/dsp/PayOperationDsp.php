<?php

/**
 * Денежные операции
 */
class PayOperationDsp
{
    /** @var \CDbConnection */
    private $db;

    public function __construct()
    {
        $this->db = Yii::app()->db;
    }

    /**
     * Возвращает имя таблицы
     * @return string
     */
    public function getTableName()
    {
        return TableNameEnum::PAY_OPERATION;
    }

    /**
     * Возвращает сумму пополнений баланса
     *
     * @return integer
     * @throws CException
     */
    public function getIncomePaySum()
    {
        $sql = "
            SELECT 
                SUM(po.sum) AS income_pay_sum 
            FROM " . $this->getTableName() . " po
            WHERE po.type_id IN (
                SELECT id 
                FROM pay_operation_type 
                WHERE name = '" . PayOperationTypeEnum::ADD_PAYMENT . "'
            )
        ";
        return $this->db->createCommand($sql)->queryScalar([]);
    }

}
