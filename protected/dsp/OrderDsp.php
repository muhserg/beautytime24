<?php

/**
 * Заказы
 *
 *
 */
class OrderDsp
{
    /**
     * @var \CDbConnection
     */
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
        return TableNameEnum::ORDER;
    }

    /**
     * Создание заказа из предварительного
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $userTypeId идентификатор типа пользователя
     * @param integer $sessionId идентификатор сессии
     *
     * @return bool
     * @throws CException
     */
    public function createFromBuf($userId, $userTypeId, $sessionId)
    {
        $sql = "
            INSERT INTO orders (
                receipt_date, 
                created_at, 
                updated_at,
                main_service_id,
                master_profile_id,
                salon_profile_id,
                total,
                plan_duration,
                fact_duration,
                rating,
                plan_price,
                address,
                address_coord,
                description,
                place,
                near_subway,
                latitude,
                longitude,
                photo_file_name,                
                owner_user_id,
                owner_user_type_id,
                status
                )
            SELECT 
                receipt_date, 
                created_at, 
                updated_at,
                main_service_id,
                master_profile_id,
                salon_profile_id,
                total,
                plan_duration,
                fact_duration,
                rating,
                plan_price,
                address,
                address_coord,
                description,
                place,
                near_subway, 
                latitude,
                longitude,
                photo_file_name,
                :user_id,
                :user_type_id,
                :order_status
            FROM buf_order
                WHERE owner_session_php_id = :session_id
        ";

        return $this->db->createCommand($sql)->execute([
            ':session_id' => $sessionId,
            ':user_id' => $userId,
            ':user_type_id' => $userTypeId,
            ':order_status' => OrderStatusEnum::CREATED
        ]);
    }

    /**
     * Число заказов
     *
     * @return integer
     * @throws CException
     */
    public function getCounts()
    {
        $sql = "
            SELECT 
                COUNT(o.id) AS order_count 
            FROM " . $this->getTableName() . " o
        ";
        return $this->db->createCommand($sql)->queryScalar([]);
    }
}
