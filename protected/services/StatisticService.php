<?php

class StatisticService
{
    private static $statisticService = null;

    /**
     * @return StatisticService
     */
    public static function getInstance()
    {
        if (self::$statisticService === null) {
            self::$statisticService = new self();
        }
        return self::$statisticService;
    }

    /**
     * Очищает параметры статистики в сессии
     *
     * @return bool
     */
    public function clearStatisticInSession()
    {
        Yii::app()->session['clientCount'] = null;
        Yii::app()->session['masterCount'] = null;
        Yii::app()->session['salonCount'] = null;
    }

    /**
     * Число зарегистрированных мастеров
     *
     * @return integer
     * @throws PublicMessageException
     */
    public function loadMasterCounts()
    {
        try {
            if (!isset(Yii::app()->session['masterCount'])) {
                Yii::app()->session['masterCount'] = (new UserDsp)->getMasterCounts();
            }
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get master counts. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить число мастеров. Ошибка в БД.');
        }
    }

    /**
     * Число парикмахеров
     *
     * @return integer
     * @throws PublicMessageException
     */
    public function getHairdresserCounts()
    {
        try {
            return (new MasterProfileDsp)->getHairdresserCounts();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get Hairdresser Counts. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить число парикмахеров. Ошибка в БД.');
        }
    }

    /**
     * Число мастеров, заполнивших профиль
     *
     * @return integer
     * @throws PublicMessageException
     */
    public function getMasterProfileCounts()
    {
        try {
            return (new MasterProfileDsp)->getCounts();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get Hairdresser Counts. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить число парикмахеров. Ошибка в БД.');
        }
    }

    /**
     * Число заказов
     *
     * @return integer
     * @throws PublicMessageException
     */
    public function getOrderCounts()
    {
        try {
            return (new OrderDsp)->getCounts();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get Order Counts. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить число заказов. Ошибка в БД.');
        }
    }

    /**
     * Сумма пополнений баланса
     *
     * @return integer
     * @throws PublicMessageException
     */
    public function getIncomePaySum()
    {
        try {
            return (new PayOperationDsp)->getIncomePaySum();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get PaySum. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить сумму пополнений баланса. Ошибка в БД.');
        }
    }

    /**
     * Число зарегистрированных клиентов
     *
     * @return integer
     * @throws PublicMessageException
     */
    public function loadClientCounts()
    {
        try {
            if (!isset(Yii::app()->session['clientCount'])) {
                Yii::app()->session['clientCount'] = (new UserDsp)->getClientCounts();
            }
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get client counts. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить число клиентов. Ошибка в БД.');
        }
    }

    /**
     * Число зарегистрированных салонов
     *
     * @return integer
     * @throws PublicMessageException
     */
    public function loadSalonCounts()
    {
        try {
            if (!isset(Yii::app()->session['salonCount'])) {
                Yii::app()->session['salonCount'] = (new UserDsp)->getSalonCounts();
            }
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get salon counts. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить число салонов. Ошибка в БД.');
        }
    }

    /**
     * Создает клик в статистике
     */
    public function createHit()
    {
        try {
            $hitModel = new HitModel;
            $hitModel->session_db_id = $_SESSION["id"];
            $hitModel->uri = $_SERVER["REQUEST_URI"];
            $hitModel->user_id = isset(Yii::app()->session['user']['id']) ? Yii::app()->session['user']['id'] : null;

            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $userIp = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $userIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $userIp = $_SERVER['REMOTE_ADDR'];
            }
            $hitModel->user_ip = $userIp;

            if (!$hitModel->save()) {
                BtLogger::getLogger()->error('Can not save hit in statistic.', [
                    'session_id' => $_SESSION["id"],
                    'error' => $hitModel->getErrors(),
                ]);
            }

        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save hit in statistic. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);
        }
    }

    /**
     * Создает сессию в статистике
     *
     * @return bool
     */
    public function createSession()
    {
        try {
            $sessionModel = new SessionModel;
            $sessionModel->session_php_id = session_id();
            $sessionModel->uri_begin = $_SERVER["REQUEST_URI"];
            $sessionModel->user_id = isset(Yii::app()->session['user']['id']) ? Yii::app()->session['user']['id'] : null;
            $sessionModel->user_agent = $_SERVER["HTTP_USER_AGENT"];

            if (!$sessionModel->save()) {
                BtLogger::getLogger()->error('Can not save session in statistic.', [
                    'session_php_id' => session_id(),
                    'error' => $sessionModel->getErrors(),
                ]);
            }

            $_SESSION["id"] = $sessionModel->id;
            return true;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') > 0) {
                $_SESSION["id"] = SessionModel::model()->findByAttributes(['session_php_id' => session_id()])->id;
                return true;
            }

            BtLogger::getLogger()->error('Can not save session in statistic. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);
        }
    }

    /**
     * Очистка статистики
     *
     * integer $days сколько дней хранить статистику
     */
    public function clearStatistic($days = STATISTIC_DAYS)
    {
        try {
            HitModel::model()->deleteAll([
                'condition' => 'created_at < DATE_SUB(NOW(), INTERVAL ' . IntVal($days) . ' DAY)',
            ]);

            SessionModel::model()->deleteAll([
                'condition' => 'created_at < DATE_SUB(NOW(), INTERVAL ' . IntVal($days) . ' DAY) AND ' .
                    'id NOT IN (SELECT session_db_id FROM hit)',
            ]);

            echo 'Statistic older ' . IntVal($days) . ' days deleted.';
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not delete old statistic. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);
        }
    }


    /**
     * Определение активности пользователя
     *
     * integer $minutes сколько дней хранить статистику
     *
     * @return bool
     */
    public function userActivity($userId, $hitTime = null, $minutes = MINUTES_FOR_ACTIVE_USER)
    {
        try {
            if ($hitTime !== null) {
                $diff = (new DateTime())->getTimestamp() - (new DateTime($hitTime))->getTimestamp();
                return ($diff <= 60 * MINUTES_FOR_ACTIVE_USER);
            }

            $hitModel = HitModel::model()->findByAttributes([
                'user_id' => $userId,
            ], [
                'condition' => 'created_at BETWEEN DATE_SUB(NOW(), INTERVAL ' . IntVal($minutes) . ' MINUTE) AND NOW()',
            ]);

            return ($hitModel !== null);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get active user. DB error.', [
                'method' => __METHOD__,
                'error' => $e,
            ]);
        }
    }
}
