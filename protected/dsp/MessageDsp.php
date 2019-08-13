<?php

/**
 * Сообщения
 */
class MessageDsp
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
        return TableNameEnum::MESSAGE;
    }

    /**
     * Получение списка пользователей, с которыми был контакт у данного пользователя
     *
     * @param integer $userId идентификатор пользователя
     *
     * @return array
     * @throws Exception
     */
    public function getUsersByUser($userId)
    {
        $sql = "
            SELECT 
                   t_user.user_id,
                   m.id AS last_message_id,
                   m.created_at AS last_message_date,
                   m.message AS last_message,
                   
                   CONCAT(cp.last_name, ' ', cp.first_name) AS cp_fio,
                   cp.small_avatar_file_name AS cp_avatar,
                   CONCAT(mp.last_name, ' ', mp.first_name) AS mp_fio,
                   mp.small_avatar_file_name AS mp_avatar,
                   sp.name AS sp_name,
                   sp.logo_file_name AS sp_avatar
            FROM (
                SELECT 
                       user_id, 
                       MAX(max_message_id) AS max_message_id 
                FROM (
                        SELECT 
                            um.to_user_id AS user_id,
                            MAX(um.message_id) AS max_message_id  
                        FROM users_message um
                        WHERE um.from_user_id = :user_id  
                        GROUP BY um.to_user_id
                            
                        UNION
                        
                        SELECT 
                            um.from_user_id AS user_id,
                            MAX(um.message_id) AS max_message_id
                        FROM users_message um
                        WHERE um.to_user_id = :user_id2
                        GROUP BY um.from_user_id
                ) t
                GROUP BY user_id
            ) t_user
            LEFT JOIN client_profile cp ON cp.user_id = t_user.user_id
            LEFT JOIN master_profile mp ON mp.user_id = t_user.user_id
            LEFT JOIN salon_profile sp ON sp.user_id = t_user.user_id
            LEFT JOIN message m ON m.id = t_user.max_message_id
        ";

        return $this->db->createCommand($sql)
            ->bindValue(':user_id', $userId)
            ->bindValue(':user_id2', $userId)
            ->queryAll(true);
    }

}
