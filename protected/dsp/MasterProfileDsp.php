<?php

/**
 * Профайл мастера
 */
class MasterProfileDsp
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
        return TableNameEnum::MASTER_PROFILE;
    }

    /**
     * Возвращает список мастеров
     *
     * @param integer $directionId Идентификатор направления
     * @param integer $serviceId Идентификатор услуги
     * @param string $fio ФИО мастера
     * @param float $clientLatitude широта адреса клиента
     * @param float $clientLongitude долгота адреса клиента
     * @param float $searchLatitude широта адреса, указанного для поиска
     * @param float $searchLongitude долгота адреса, указанного для поиска
     * @param string $workExperiencePeriod стаж работы в диапазоне
     * @param string $sort сортировка списка мастеров
     *
     * @return array
     * @throws Exception
     */
    public function findMaster(
        $directionId,
        $serviceId,
        $fio,
        $clientLatitude = null,
        $clientLongitude = null,
        $searchLatitude = null,
        $searchLongitude = null,
        $workExperiencePeriod = null,
        $subway = null,
        $sort = null
    ) {
        $sqlWhere = '';

        $sqlDistance = '';
        if (!empty($searchLatitude) && !empty($searchLongitude)) {
            $sqlDistance = ' (' . (new GeoHelper)->earthRadius . ' * ACOS(
                COS(:client_lat) * COS(mp.latitude) * COS(:client_lng - mp.longitude) + SIN(:client_lat) * SIN(mp.latitude) )
                ) <= IFNULL(n.radius*1000, ' . MASTER_RADIUS_FROM_CLIENT . ')';
        }
        $fioSql = '';
        if (!empty($fio)) {
            $fioSql = ' (mp.first_name LIKE :first_name OR mp.last_name LIKE :last_name)';
        }

        if (!empty($sqlDistance) && empty($fioSql)) {
            $sqlWhere = $sqlDistance;
        } elseif (empty($sqlDistance) && !empty($fioSql)) {
            $sqlWhere = $fioSql;
        } elseif (!empty($sqlDistance) && !empty($fioSql)) {
            $sqlWhere = $sqlDistance . ' AND ' . $fioSql;
        }

        //===============================================================

        $sqlDirServ = '';
        if (!empty($directionId)) {
            $sqlDirServ = 'INNER JOIN master_profile_service mps ON mps.profile_id = mp.id 
                                AND service_id IN (
                                    SELECT id 
                                    FROM service 
                                    WHERE direction_id = :direction_id
                                )
            ';
        }
        if (!empty($serviceId)) {
            $sqlDirServ = 'INNER JOIN master_profile_service mps ON mps.profile_id = mp.id 
                               AND service_id = :service_id
            ';
        }

        if (!empty($workExperiencePeriod)) {
            if ($workExperiencePeriod === 'oneYear') {
                $sqlWorkExperience = 'mp.work_experience < 1';
            } elseif ($workExperiencePeriod === 'threeYear') {
                $sqlWorkExperience = 'mp.work_experience >= 1 AND mp.work_experience < 3';
            } elseif ($workExperiencePeriod === 'manyYear') {
                $sqlWorkExperience = 'mp.work_experience >= 3';

                if ($sqlWhere === '') {
                    $sqlWhere = $sqlWorkExperience;
                } else {
                    $sqlWhere .= ' AND ' . $sqlWorkExperience;
                }
            }
        }

        if (!empty($subway)) {
            if ($sqlWhere === '') {
                $sqlWhere = 'mp.near_subway = :subway';
            } else {
                $sqlWhere .= ' AND mp.near_subway = :subway';
            }
        }

        if ($sqlWhere !== '') {
            $sqlWhere = ' WHERE ' . $sqlWhere;
        }

        $sqlSort = '';
        if (!empty($sort)) {
            if ($sort === 'rating') {
                $sqlSort = 'mp.rating DESC';
            } elseif ($sort === 'address') {
                $sqlSort = '(' . (new GeoHelper)->earthRadius . ' * ACOS(
                COS(:client_lat2) * COS(mp.latitude) * COS(:client_lng2 - mp.longitude) + SIN(:client_lat2) * SIN(mp.latitude) )
                )';
            }

            $sqlSort = ' ORDER BY ' . $sqlSort;
        }

        $sql = "
            SELECT DISTINCT
                mp.*,
                DATE_FORMAT(ws.time_begin, '%H:%i') as time_begin,
                DATE_FORMAT(ws.time_end, '%H:%i') as time_end,
                h.created_at AS last_hit_time
            FROM " . $this->getTableName() . " mp
            LEFT JOIN work_schedule ws ON ws.profile_id = mp.id 
                AND ws.user_id = mp.user_id
                AND ws.weekdays LIKE :curr_weekday
            LEFT JOIN hit h ON h.id = (
                SELECT MAX(id) 
                FROM hit 
                WHERE user_id = mp.user_id
            )
            LEFT JOIN notify n ON n.user_id = mp.user_id
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
     * Возвращает Список случайных  мастеров
     *
     * @param integer $randCount число случайных мастеров
     * @return array
     * @throws CException
     */
    public function findRandMaster($randCount)
    {
        $sql = "
            SELECT 
                mp.*,
                DATE_FORMAT(ws.time_begin, '%H:%i') as time_begin,
                DATE_FORMAT(ws.time_end, '%H:%i') as time_end,
                h.created_at AS last_hit_time
            FROM " . $this->getTableName() . " mp
            LEFT JOIN work_schedule ws ON ws.user_id = mp.user_id 
                AND weekdays LIKE :curr_weekday
            LEFT JOIN hit h ON h.id = (
                SELECT MAX(id) 
                FROM hit 
                WHERE user_id = mp.user_id
            )
            ORDER BY rand()
            LIMIT " . IntVal($randCount) . "
        ";
        return $this->db->createCommand($sql)
            ->bindValue(':curr_weekday', '%' . date('N') . '%')
            ->queryAll(true);
    }


    /**
     * Число парикмахеров
     *
     * @return integer
     * @throws CException
     */
    public function getHairdresserCounts()
    {
        $sql = "
            SELECT 
                count(DISTINCT mp.id) AS master_count 
            FROM " . $this->getTableName() . " mp
            INNER JOIN master_profile_service mps ON mps.profile_id = mp.id
            INNER JOIN service s ON s.id = mps.service_id
            INNER JOIN direction d ON d.id = s.direction_id
            WHERE d.name = :direction_name
        ";
        return $this->db->createCommand($sql)->queryScalar([
            ':direction_name' => DirectionNameEnum::HAIRDRESSER,
        ]);
    }

    /**
     * Число мастеров, заполнивших профиль
     *
     * @return integer
     * @throws CException
     */
    public function getCounts()
    {
        $sql = "
            SELECT 
                count(mp.id) AS master_count 
            FROM " . $this->getTableName() . " mp
        ";
        return $this->db->createCommand($sql)->queryScalar([]);
    }

    /**
     * Возвращает cписок телефонов мастеров для модерации
     *
     * @return array
     * @throws CException
     */
    public function findForModerate()
    {
        $sql = "
            SELECT u.phone
            FROM " . $this->getTableName() . " mp
            INNER JOIN user u ON u.id = mp.user_id 
            WHERE DATEDIFF(now(), u.created_at) <= " . DAYS_FOR_NEW_USER;

        return $this->db->createCommand($sql)
            ->queryColumn();
    }

    /**
     * Возвращает Список несогласованных мастеров по заказу, которым сделали предложение
     *
     * @param integer $orderId идентификатор заказа
     *
     * @return array
     * @throws CException
     */
    public function findNotAgreeMaster($orderId)
    {
        $sql = "
            SELECT 
                mp.*,
                mps.cost AS client_service_cost,
                DATE_FORMAT(ws.time_begin, '%H:%i') as time_begin,
                DATE_FORMAT(ws.time_end, '%H:%i') as time_end,
                h.created_at AS last_hit_time
            FROM " . $this->getTableName() . " mp
            LEFT JOIN work_schedule ws ON ws.user_id = mp.user_id
            LEFT JOIN hit h ON h.id = (
                SELECT MAX(id) 
                FROM hit 
                WHERE user_id = mp.user_id
            )
            INNER JOIN buf_master_order bmo ON bmo.profile_id = mp.id
                AND bmo.order_id = :order_id  
                AND bmo.master_agree = " . AgreeTypeEnum::NOT_AGREE . " 
            LEFT JOIN orders o ON o.id = bmo.order_id 
            LEFT JOIN master_profile_service mps ON mps.profile_id = mp.id 
                AND mps.service_id = o.main_service_id 
            ORDER BY 
                bmo.created_at DESC
        ";
        return $this->db->createCommand($sql)
            ->bindValue(':order_id', $orderId)
            ->queryAll(true);
    }

    /**
     * Возвращает Список несогласованных мастеров по заказу, которые сами сделали предложение
     *
     * @param integer $orderId идентификатор заказа
     *
     * @return array
     * @throws CException
     */
    public function findNotAgreeOwnMaster($orderId)
    {
        $sql = "
            SELECT 
                mp.*,
                mps.cost AS client_service_cost,
                DATE_FORMAT(ws.time_begin, '%H:%i') as time_begin,
                DATE_FORMAT(ws.time_end, '%H:%i') as time_end,
                h.created_at AS last_hit_time
            FROM " . $this->getTableName() . " mp
            LEFT JOIN work_schedule ws ON ws.user_id = mp.user_id
            LEFT JOIN hit h ON h.id = (
                SELECT MAX(id) 
                FROM hit 
                WHERE user_id = mp.user_id
            )
            INNER JOIN buf_master_order bmo ON bmo.profile_id = mp.id
                AND bmo.order_id = :order_id  
                AND bmo.master_agree = " . AgreeTypeEnum::AGREE . "
            LEFT JOIN orders o ON o.id = bmo.order_id 
            LEFT JOIN master_profile_service mps ON mps.profile_id = mp.id 
                AND mps.service_id = o.main_service_id 
            ORDER BY 
                bmo.created_at DESC
        ";
        return $this->db->createCommand($sql)
            ->bindValue(':order_id', $orderId)
            ->queryAll(true);
    }


    /**
     * Возвращает список мастеров, которые подходят для привязки к заказу
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
    public function findMasterByOrder(
        $serviceId,
        $orderDate,
        $clientOrOrderLatitude,
        $clientOrOrderLongitude,
        $clientOrOrderNearSubway
    ) {
        if (!empty($orderDate)) {
            $sqlOrderDate = ' INNER JOIN work_schedule ws ON ws.user_id = mp.user_id AND weekdays LIKE :receipt_date_weekday ';
        } else {
            $sqlOrderDate = ' LEFT JOIN work_schedule ws ON ws.user_id = mp.user_id AND weekdays LIKE :receipt_date_weekday ';
        }
        $sqlDistance = '';
        if (!empty($clientOrOrderLatitude) && !empty($clientOrOrderLongitude)) {
            $sqlAcos = (new GeoHelper)->earthRadius . ' * ACOS(' .
                'COS(:client_lat) * COS(mp.latitude) * COS(:client_lng - mp.longitude) + SIN(:client_lat) * SIN(mp.latitude)' .
                ')';
            $sqlAcos2 = (new GeoHelper)->earthRadius . ' * ACOS(' .
                'COS(:client_lat2) * COS(mp.latitude) * COS(:client_lng2 - mp.longitude) + SIN(:client_lat2) * SIN(mp.latitude)' .
                ')';
            $sqlDistance = ' WHERE ( ' . $sqlAcos . ' <= ' . MASTER_RADIUS_FROM_CLIENT . ' AND n.type IS NULL) OR ' .
                '( ' . $sqlAcos2 . ' <= n.radius*1000 AND n.type = \'' . NotifyTypeEnum::BY_RADIUS . '\') OR ' .
                '( mp.near_subway = :client_near_subway AND mp.near_subway IS NOT NULL AND n.type = \'' . NotifyTypeEnum::BY_NEAR_SUBWAY . '\')';
        }

        $sql = "
            SELECT 
                mp.*,
                mps.cost AS client_service_cost,
                DATE_FORMAT(ws.time_begin, '%H:%i') as time_begin,
                DATE_FORMAT(ws.time_end, '%H:%i') as time_end,
                h.created_at AS last_hit_time
            FROM " . $this->getTableName() . " mp
               " . $sqlOrderDate . "
            INNER JOIN master_profile_service mps ON mps.profile_id = mp.id 
                AND mps.service_id = :service_id
            LEFT JOIN hit h ON h.id = (
                SELECT MAX(id) 
                FROM hit 
                WHERE user_id = mp.user_id
            )  
            LEFT JOIN notify n ON n.user_id = mp.user_id
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
     * Рассчитывает рейтинг мастера
     *
     * @param integer $profileId идентификатор профиля мастера
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
                AND o.master_profile_id = :profileId
            INNER JOIN master_profile mp ON mp.id = :profileId2 
            WHERE r.user_id <> mp.user_id
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
