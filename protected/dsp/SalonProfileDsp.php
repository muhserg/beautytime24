<?php

/**
 * Профайл салона
 */
class SalonProfileDsp
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
        return TableNameEnum::SALON_PROFILE;
    }

    /**
     * Возвращает список салонов
     *
     * @param integer $directionId Идентификатор направления
     * @param integer $serviceId Идентификатор услуги
     * @param float $clientLatitude широта адреса клиента
     * @param float $clientLongitude долгота адреса клиента
     * @param float $searchLatitude широта адреса, указанного для поиска
     * @param float $searchLongitude долгота адреса, указанного для поиска
     * @param string $sort сортировка списка мастеров
     *
     * @return array
     * @throws Exception
     */
    public function findSalon($directionId,
                              $serviceId,
                              $clientLatitude = null,
                              $clientLongitude = null,
                              $searchLatitude = null,
                              $searchLongitude = null,
                              $subway = null,
                              $sort = null)
    {
        $sqlWhere = '';

        $sqlDistance = '';
        if (!empty($searchLatitude) && !empty($searchLongitude)) {
            $sqlDistance = ' (' . (new GeoHelper)->earthRadius . ' * ACOS(
                COS(:client_lat) * COS(sp.latitude) * COS(:client_lng - sp.longitude) + SIN(:client_lat) * SIN(sp.latitude) )
                ) <= IFNULL(n.radius*1000, ' . SALON_RADIUS_FROM_CLIENT . ')';
        }

        if (!empty($sqlDistance)) {
            $sqlWhere = $sqlDistance;
        }

        //===============================================================

        $sqlDirServ = '';
        if (!empty($directionId)) {
            $sqlDirServ = 'INNER JOIN salon_profile_service sps ON sps.profile_id = sp.id 
                                AND service_id IN (
                                    SELECT id 
                                    FROM service 
                                    WHERE direction_id = :direction_id
                                )
            ';
        }
        if (!empty($serviceId)) {
            $sqlDirServ = 'INNER JOIN salon_profile_service sps ON sps.profile_id = sp.id 
                               AND service_id = :service_id
            ';
        }

        if (!empty($subway)) {
            if ($sqlWhere === '') {
                $sqlWhere = 'sp.near_subway = :subway';
            } else {
                $sqlWhere .= ' AND sp.near_subway = :subway';
            }
        }

        if ($sqlWhere !== '') {
            $sqlWhere = ' WHERE ' . $sqlWhere;
        }

        $sqlSort = '';
        if (!empty($sort)) {
            if ($sort === 'rating') {
                $sqlSort = 'sp.rating DESC';
            } elseif ($sort === 'address') {
                $sqlSort = '(' . (new GeoHelper)->earthRadius . ' * ACOS(
                COS(:client_lat2) * COS(sp.latitude) * COS(:client_lng2 - sp.longitude) + SIN(:client_lat2) * SIN(sp.latitude) )
                )';
            }

            $sqlSort = ' ORDER BY ' . $sqlSort;
        }

        $sql = "
            SELECT DISTINCT
                sp.*,
                DATE_FORMAT(ws.time_begin, '%H:%i') as time_begin,
                DATE_FORMAT(ws.time_end, '%H:%i') as time_end,
                h.created_at AS last_hit_time
            FROM " . $this->getTableName() . " sp
            LEFT JOIN work_schedule ws ON ws.profile_id = sp.id 
                AND ws.user_id = sp.user_id
                AND ws.weekdays LIKE :curr_weekday
            LEFT JOIN hit h ON h.id = (
                SELECT MAX(id) 
                FROM hit 
                WHERE user_id = sp.user_id
            )
            LEFT JOIN notify n ON n.user_id = sp.user_id
        " . $sqlDirServ . $sqlWhere . $sqlSort;

        $command = $this->db->createCommand($sql);
        if (!empty($directionId)) {
            $command->bindValue(':direction_id', $directionId);
        }
        if (!empty($serviceId)) {
            $command->bindValue(':service_id', $serviceId);
        }
        $command->bindValue(':curr_weekday', '%' . (new DateTime())->format('N') . '%');

        if (!empty($fio)) {
            $command->bindValue(':first_name', $fio . '%');
            $command->bindValue(':last_name', $fio . '%');
        }
        if (!empty($searchLatitude) && !empty($searchLongitude)) {
            $command->bindValue(':client_lat', $searchLatitude);
            $command->bindValue(':client_lng', $searchLongitude);
        }
        if (!empty($subway)) {
            $command->bindValue(':subway', $subway);
        }

        if ($sort === 'address') {
            $command->bindValue(':client_lat2', $clientLatitude);
            $command->bindValue(':client_lng2', $clientLongitude);
        }

        return $command->queryAll(true);
    }

    /**
     * Возвращает Список случайных салонов
     *
     * @param integer $randCount число случайных салонов
     * @return array
     * @throws CException
     */
    public function findRandSalon($randCount)
    {
        $sql = "
            SELECT 
                ms.*,
                DATE_FORMAT(ws.time_begin, '%H:%i') as time_begin,
                DATE_FORMAT(ws.time_end, '%H:%i') as time_end,
                h.created_at AS last_hit_time
            FROM " . $this->getTableName() . " ms
            LEFT JOIN work_schedule ws ON ws.user_id = ms.user_id  
                AND weekdays LIKE :curr_weekday
            LEFT JOIN hit h ON h.id = (
                SELECT MAX(id) 
                FROM hit 
                WHERE user_id = ms.user_id
            )
            ORDER BY rand()
            LIMIT " . IntVal($randCount) . "
        ";
        return $this->db->createCommand($sql)
            ->bindValue(':curr_weekday', '%' . date('N') . '%')
            ->queryAll(true);
    }


    /**
     * Возвращает Список несогласованных салонов по заказу, которым сделали предложение
     *
     * @param integer $orderId идентификатор заказа
     *
     * @return array
     * @throws CException
     */
    public function findNotAgreeSalon($orderId)
    {
        $sql = "
            SELECT 
                sp.*,
                sps.cost AS client_service_cost,
                DATE_FORMAT(ws.time_begin, '%H:%i') as time_begin,
                DATE_FORMAT(ws.time_end, '%H:%i') as time_end,
                h.created_at AS last_hit_time
            FROM " . $this->getTableName() . " sp
            LEFT JOIN work_schedule ws ON ws.user_id = sp.user_id
            LEFT JOIN hit h ON h.id = (
                SELECT MAX(id) 
                FROM hit 
                WHERE user_id = sp.user_id
            )
            INNER JOIN buf_salon_order bso ON bso.profile_id = sp.id
                AND bso.order_id = :order_id  
                AND bso.salon_agree = " . AgreeTypeEnum::NOT_AGREE . " 
            LEFT JOIN orders o ON o.id = bso.order_id 
            LEFT JOIN salon_profile_service sps ON sps.profile_id = sp.id 
                AND sps.service_id = o.main_service_id
            ORDER BY 
                bso.created_at DESC
        ";
        return $this->db->createCommand($sql)
            ->bindValue(':order_id', $orderId)
            ->queryAll(true);
    }


    /**
     * Возвращает Список несогласованных салонов по заказу, которые сами сделали предложение
     *
     * @param integer $orderId идентификатор заказа
     *
     * @return array
     * @throws CException
     */
    public function findNotAgreeOwnSalon($orderId)
    {
        $sql = "
            SELECT 
                sp.*,
                sps.cost AS client_service_cost,
                DATE_FORMAT(ws.time_begin, '%H:%i') as time_begin,
                DATE_FORMAT(ws.time_end, '%H:%i') as time_end,
                h.created_at AS last_hit_time
            FROM " . $this->getTableName() . " sp
            LEFT JOIN work_schedule ws ON ws.user_id = sp.user_id
            LEFT JOIN hit h ON h.id = (
                SELECT MAX(id) 
                FROM hit 
                WHERE user_id = sp.user_id
            )
            INNER JOIN buf_salon_order bso ON bso.profile_id = sp.id
                AND bso.order_id = :order_id  
                AND bso.salon_agree = " . AgreeTypeEnum::AGREE . " 
            LEFT JOIN orders o ON o.id = bso.order_id 
            LEFT JOIN salon_profile_service sps ON sps.profile_id = sp.id 
                AND sps.service_id = o.main_service_id
            ORDER BY 
                bso.created_at DESC
        ";
        return $this->db->createCommand($sql)
            ->bindValue(':order_id', $orderId)
            ->queryAll(true);
    }

    /**
     * Возвращает список салонов, которые подходят для привязки к заказу
     *
     * @param integer $serviceId услуга заказа
     * @param string $orderDate дата посещения в заказе
     * @param float $clientOrOrderLatitude широта адреса заказа, либо адреса клиента
     * @param float $clientOrOrderLongitude долгота адреса заказа, либо адреса клиента
     * @param string $clientOrOrderNearSubway ближайшее метро адреса заказа, либо адреса клиента
     *
     * @return array
     * @throws Exception
     */
    public function findSalonByOrder(
        $serviceId,
        $orderDate,
        $clientOrOrderLatitude,
        $clientOrOrderLongitude,
        $clientOrOrderNearSubway)
    {
        if (!empty($orderDate)) {
            $sqlOrderDate = ' INNER JOIN work_schedule ws ON ws.user_id = sp.user_id AND weekdays LIKE :receipt_date_weekday ';
        } else {
            $sqlOrderDate = ' LEFT JOIN work_schedule ws ON ws.user_id = sp.user_id AND weekdays LIKE :receipt_date_weekday ';
        }
        $sqlDistance = '';
        if (!empty($clientOrOrderLatitude) && !empty($clientOrOrderLongitude)) {
            $sqlAcos = (new GeoHelper)->earthRadius . ' * ACOS(' .
                'COS(:client_lat) * COS(sp.latitude) * COS(:client_lng - sp.longitude) + SIN(:client_lat) * SIN(sp.latitude)' .
                ')';
            $sqlAcos2 = (new GeoHelper)->earthRadius . ' * ACOS(' .
                'COS(:client_lat2) * COS(sp.latitude) * COS(:client_lng2 - sp.longitude) + SIN(:client_lat2) * SIN(sp.latitude)' .
                ')';
            $sqlDistance = ' WHERE ( ' . $sqlAcos . ' <= ' . SALON_RADIUS_FROM_CLIENT . ' AND n.type IS NULL) OR ' .
                '( ' . $sqlAcos2 . ' <= n.radius*1000 AND n.type = \'' . NotifyTypeEnum::BY_RADIUS . '\') OR ' .
                '( sp.near_subway = :client_near_subway AND sp.near_subway IS NOT NULL AND n.type = \'' . NotifyTypeEnum::BY_NEAR_SUBWAY . '\')';
        }

        $sql = "
            SELECT 
                sp.*,
                sps.cost AS client_service_cost,
                DATE_FORMAT(ws.time_begin, '%H:%i') as time_begin,
                DATE_FORMAT(ws.time_end, '%H:%i') as time_end,
                h.created_at AS last_hit_time
            FROM " . $this->getTableName() . " sp
               " . $sqlOrderDate . "
            INNER JOIN salon_profile_service sps ON sps.profile_id = sp.id 
                AND sps.service_id = :service_id
            LEFT JOIN hit h ON h.id = (
                SELECT MAX(id) 
                FROM hit 
                WHERE user_id = sp.user_id
            )  
            LEFT JOIN notify n ON n.user_id = sp.user_id
        " . $sqlDistance;

        $command = $this->db->createCommand($sql);
        $command->bindValue(':service_id', $serviceId);
        if (!empty($orderDate)) {
            $command->bindValue(':receipt_date_weekday', '%' . (new DateTime($orderDate))->format('N') . '%');
        } else {
            $command->bindValue(':receipt_date_weekday', '%' . (new DateTime())->format('N') . '%');
        }
        if (!empty($clientOrOrderLatitude) && !empty($clientOrOrderLongitude)) {
            $command->bindValue(':client_lat', $clientOrOrderLatitude);
            $command->bindValue(':client_lng', $clientOrOrderLongitude);
            $command->bindValue(':client_lat2', $clientOrOrderLatitude);
            $command->bindValue(':client_lng2', $clientOrOrderLongitude);
            $command->bindValue(':client_near_subway', $clientOrOrderNearSubway);
        }
        return $command->queryAll(true);
    }

    /**
     * Рассчитывает рейтинг салона
     *
     * @param integer $profileId идентификатор профиля салона
     *
     * @return integer
     * @throws CException
     */
    public function calcRating($profileId)
    {
        $sql = "
            SELECT 
                SUM(r.assessment)/COUNT(r.id)
            FROM review r
            INNER JOIN orders o ON o.id = r.order_id 
                AND o.salon_profile_id = :profileId
            INNER JOIN salon_profile sp ON sp.id = :profileId2 
            WHERE r.user_id <> sp.user_id
        ";
        $rating = $this->db->createCommand($sql)
            ->bindValue(':profileId', $profileId)
            ->bindValue(':profileId2', $profileId)
            ->queryScalar();

        if ($rating === null) {
            return 0;
        }

        return $rating;
    }
}
