<?php

/**
 * Обеспечивает работу с отзывами
 */
class ReviewService
{
    private static $reviewService = null;

    /** var DateFormatter */
    private $dateFormatter;

    /** var CDbConnection */
    private $db;

    /**
     * @return ReviewService
     */
    public static function getInstance()
    {
        if (self::$reviewService === null) {
            self::$reviewService = new self();
        }
        return self::$reviewService;
    }

    public function __construct()
    {
        $this->dateFormatter = new DateFormatter();
        $this->db = Yii::app()->db;
    }


    /**
     * Получение отзывов пользователей, привязанных к конкретному заказу
     *
     * @param integer $orderId идентификатор заказа
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getByOrder($orderId)
    {
        try {
            $reviewModels = ReviewModel::model()->with('clientProfile')->with('order')->findAllByAttributes([
                'order_id' => $orderId,
            ],
                ['select' => 'id, created_at, assessment, text']
            );
            if (empty($reviewModels)) {
                return [];
            }

            return $this->getFullArrayReview($reviewModels);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get reviews. Fatal error.', [
                'orderId' => $orderId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить отзывы пользователя. Ошибка в БД.');
        }
    }


    /**
     * Получение рандомных отзывов
     *
     * @param integer $count кол-во отзывов, для вывода
     *
     * @return array
     * @throws PublicMessageException
     */
    public function randReview($count)
    {
        try {
            $reviewModels = ReviewModel::model()->with('clientProfile')->with('order')->findAllByAttributes([
                'is_moderated' => true,
            ], [
                'select' => 'id, created_at, order_id, assessment, text',
                'order' => 'rand()',
                'limit' => $count,
            ]);
            if (empty($reviewModels)) {
                return [];
            }

            return $this->getFullArrayReview($reviewModels);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get reviews. Fatal error.', [
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить отзывы пользователя. Ошибка в БД.');
        }
    }

    /**
     * Получение отзывов, сделанных конкретным пользователем
     *
     * @param integer $userId идентификатор пользователя
     * @param integer $limit кол-во выводимых отзывов
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getByUser($userId, $limit)
    {
        try {
            $reviewModels = ReviewModel::model()->with('clientProfile')->with('order')->findAllByAttributes(
                ['user_id' => $userId],
                [
                    'order' => '`t`.created_at DESC',
                    'limit' => $limit,
                ]
            );
            if (empty($reviewModels)) {
                return [];
            }

            return $this->getFullArrayReview($reviewModels);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get reviews. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить отзывы пользователя. Ошибка в БД.');
        }
    }

    /**
     * Получение всех отзывов клиентов, сделанных в заказах данного мастера
     *
     * @param integer $userId идентификатор пользователя - мастера
     * @param integer $limit кол-во выводимых отзывов
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getByMaster($userId, $limit)
    {
        try {
            $reviewModels = ReviewModel::model()->with('clientProfile')->with('order')->findAll([
                'select' => '*',
                'condition' => 'order_id IN (
                        SELECT o.id 
                        FROM orders o, 
                            master_profile mp 
                        WHERE o.master_profile_id = mp.id 
                            AND mp.user_id = :user_id
                    )',
                'params' => [':user_id' => $userId],
                'order' => '`t`.created_at DESC',
                'limit' => $limit,
            ]);
            if (empty($reviewModels)) {
                return [];
            }

            return $this->getFullArrayReview($reviewModels);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get reviews. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить отзывы пользователя. Ошибка в БД.');
        }
    }

    /**
     * Получение всех отзывов клиентов, сделанных в заказах данного мастера
     *
     * @param integer $userId идентификатор пользователя - мастера
     * @param integer $limit кол-во выводимых отзывов
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getBySalon($userId, $limit)
    {
        try {
            $reviewModels = ReviewModel::model()->with('clientProfile')->with('order')->findAll([
                'select' => '*',
                'condition' => 'order_id IN (
                        SELECT o.id 
                        FROM orders o, 
                            salon_profile sp 
                        WHERE o.salon_profile_id = sp.id 
                            AND sp.user_id = :user_id
                    )',
                'params' => [':user_id' => $userId],
                'order' => '`t`.created_at DESC',
                'limit' => $limit,
            ]);
            if (empty($reviewModels)) {
                return [];
            }

            return $this->getFullArrayReview($reviewModels);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get reviews. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить отзывы пользователя. Ошибка в БД.');
        }
    }

    /**
     * Получение отзыва пользователя по id
     *
     * @param integer $id идентификатор отзыва
     * @param integer $userId идентификатор пользователя
     *
     * @return ReviewModel
     * @throws PublicMessageException
     */
    public function getById($id, $userId)
    {
        try {
            $reviewModel = ReviewModel::model()->findByAttributes(['id' => $id, 'user_id' => $userId]);
            if (empty($reviewModel)) {
                throw new PublicMessageException('Не удалось найти отзыв для данного пользователя.');
            }

            return $reviewModel;
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get review. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось найти отзыв для данного пользователя. Ошибка в БД.');
        }
    }


    /**
     * Создание отзыва пользователя
     *
     * @param integer $userId идентификатор пользователя, создателя отзыва
     * @param integer $userType тип пользователя, создателя отзыва
     * @param integer $orderId идентификатор заказа
     * @param integer $assessment оценка работы мастера
     * @param string $reviewText текст отзыва
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function create($userId, $userType, $orderId, $assessment, $reviewText)
    {
        if (empty($orderId) || empty($reviewText)) {
            throw new PublicMessageException('Отсутствуют входные параметры.');
        }

        try {
            //получаем модель
            $reviewModel = new ReviewModel;

            //заполняем модель
            $reviewModel->user_id = $userId;
            $reviewModel->order_id = $orderId;
            $reviewModel->assessment = $assessment;
            $reviewModel->text = $reviewText;
            $reviewModel->is_moderated = !IS_MODERATE_REVIEWS;

            $transaction = $this->db->beginTransaction();
            if (!$reviewModel->save()) {
                BtLogger::getLogger()->error('Can not save review.', [
                    'userId' => $userId,
                    'orderId' => $orderId,
                    'error' => $reviewModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось сохранить отзыв.');
            }

            $orderModel = OrderService::getInstance()->assessment($orderId, $userId);
            if ($orderModel->master_profile_id != null) {
                ProfileService::getInstance()->calcRating($orderModel->master_profile_id, UserTypeEnum::MASTER);
            } elseif ($orderModel->salon_profile_id != null) {
                ProfileService::getInstance()->calcRating($orderModel->salon_profile_id, UserTypeEnum::SALON);
            }

            $transaction->commit();

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save review. Fatal error.', [
                'userId' => $userId,
                'orderId' => $orderId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось сохранить отзыв. Ошибка в БД.');
        }
    }

    /**
     * Выдает $randCount случайных видео-отзыва
     *
     * @param integer $randCount
     *
     * @return array
     */
    public function getRandVideoReviews($randCount)
    {
        $videoReviewsUrls = [
            'https://www.youtube.com/embed/59ytVV990zY',
            'https://www.youtube.com/embed/UR0jas4yu9g',
            'https://www.youtube.com/embed/MDTezyml5rI',
            'https://www.youtube.com/embed/WaLt6rUipAk',
            'https://www.youtube.com/embed/CpSA0bO0rUs',
            'https://www.youtube.com/embed/4OctXTc1THI'
        ];

        $videoUrls = [];
        $randKeys = array_rand($videoReviewsUrls, $randCount);
        foreach ($randKeys as $randKey) {
            $videoUrls[] = $videoReviewsUrls[$randKey];
        }

        return $videoUrls;
    }

    /**
     * Выдает массив отзывов для отображения
     *
     * @param ReviewModel[] $reviewModels
     *
     * @return array
     * @throws Exception
     */
    public function getFullArrayReview(array $reviewModels)
    {
        $reviews = [];
        foreach ($reviewModels as $reviewModel) {
            $avatarUrl = !empty($reviewModel->clientProfile->small_avatar_file_name)
                ? IMG_SMALL_DIR . $reviewModel->clientProfile->small_avatar_file_name : IMG_PATH_NO_AVATAR;

            $reviews[] = [
                'avatarUrl' => $avatarUrl,
                'img_class' => ($avatarUrl !== IMG_PATH_NO_AVATAR ? 'round_photo' : 'round_no_photo'),
                'date' => $this->dateFormatter->format($reviewModel->created_at),
                'orderId' => $reviewModel->order_id,
                'serviceName' => !empty($reviewModel->order->service->name) ? $reviewModel->order->service->name : '',
                'assessment' => $reviewModel->assessment,
                'reviewText' => $reviewModel->text,
            ];

        }

        return $reviews;
    }
}
