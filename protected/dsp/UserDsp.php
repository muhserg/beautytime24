<?php

/**
 * Учетные записи пользователей
 *
 *
 */
class UserDsp
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
        return TableNameEnum::USER;
    }

    /**
     * Возвращает число мастеров
     *
     * @return integer
     * @throws CException
     */
    public function getMasterCounts()
    {
        $sql = "
            SELECT 
                COUNT(u.id) AS master_count 
            FROM " . $this->getTableName() . " u
            INNER JOIN user_type ut ON ut.id = u.type
            WHERE ut.name = :type
        ";
        return $this->db->createCommand($sql)->queryScalar([
            ':type' => UserTypeEnum::MASTER,
        ]);
    }

    /**
     * Возвращает число клиентов
     *
     * @return integer
     * @throws CException
     */
    public function getClientCounts()
    {
        $sql = "
            SELECT 
                COUNT(u.id) AS client_count 
            FROM " . $this->getTableName() . " u
            INNER JOIN user_type ut ON ut.id = u.type
            WHERE ut.name = :type
        ";
        return $this->db->createCommand($sql)->queryScalar([
            ':type' => UserTypeEnum::CLIENT,
        ]);
    }

    /**
     * Возвращает число салонов
     *
     * @return integer
     * @throws CException
     */
    public function getSalonCounts()
    {
        $sql = "
            SELECT 
                count(u.id) AS client_count 
            FROM " . $this->getTableName() . " u
            INNER JOIN user_type ut ON ut.id = u.type
            WHERE ut.name = :type
        ";
        return $this->db->createCommand($sql)->queryScalar([
            ':type' => UserTypeEnum::SALON,
        ]);
    }

    /**
     * Возвращает всех пользователей, кроме админов
     *
     * @return array
     * @throws CException
     */
    public function getAllWithoutAdmin()
    {
        $sql = "
            SELECT 
                u.id,
                ut.name AS user_type,
                u.login, 
                u.phone,
                u.email,
                DATE_FORMAT(u.created_at, '%d.%m.%Y %H:%i') AS created_at,
                u.confirm_phone
            FROM " . $this->getTableName() . " u
            LEFT JOIN user_type ut ON ut.id = u.type
            WHERE admin_flag <> 1
            ORDER BY u.created_at DESC
        ";
        return $this->db->createCommand($sql)->queryAll();
    }

    /**
     * Пересчет баланса пользователя
     *
     * @param integer $userId идентификатор пользователя
     * @return integer
     * @throws CException
     */
    public function calcBalance($userId)
    {
        $sql = "UPDATE " . $this->getTableName() . " u
            SET balance = (
                            SELECT SUM(sum) 
                            FROM pay_operation 
                            WHERE user_id = u.id
                          )
            WHERE u.id = :user_id
        ";
        return $this->db->createCommand($sql)->execute([
            ':user_id' => $userId
        ]);
    }
}
