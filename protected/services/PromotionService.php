<?php

/**
 * Обеспечивает работу с акциями
 */
class PromotionService
{
    private static $promotionService = null;

    /** var ImageHelper */
    private $imageHelper;

    /** var DateFormatter */
    private $dateFormatter;

    /**
     * @return PromotionService
     */
    public static function getInstance()
    {
        if (self::$promotionService === null) {
            self::$promotionService = new self();
        }
        return self::$promotionService;
    }

    public function __construct()
    {
        $this->imageHelper = new ImageHelper();
        $this->dateFormatter = new DateFormatter();
    }

    /**
     * Получение акций пользователя
     *
     * @param integer $userId идентификатор пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getByUser($userId)
    {
        try {
            $promotionModels = PromotionModel::model()->findAllByAttributes(['owner_user_id' => $userId]);
            if (empty($promotionModels)) {
                return [];
            }

            return $this->getFullArrayReview($promotionModels);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get promotions. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить акции пользователя. Ошибка в БД.');
        }
    }


    /**
     * Получение рандомных акций
     *
     * @param integer $count кол-во отзывов, для вывода
     *
     * @return array
     * @throws PublicMessageException
     */
    public function randReview($count, $clientLatitude = null, $clientLongitude = null)
    {
        try {
            if (!empty($clientLatitude) && !empty($clientLongitude)) {
                $promotionModels = PromotionModel::model()->findAllByAttributes([], [
                    'select' => '*',
                    'condition' => 'date_begin <= NOW() AND (date_end IS NULL OR date_end >= NOW()) AND ' .
                        '(' . (new GeoHelper)->earthRadius . ' * ACOS(
                              COS(:client_lat) * COS(`t`.latitude) * COS(:client_lng - `t`.longitude) + SIN(:client_lat) * SIN(`t`.latitude)
                           ) <= ' . MASTER_RADIUS_FROM_CLIENT .
                        ')',
                    'params' => [':client_lat' => $clientLatitude, ':client_lng' => $clientLongitude],
                    'order' => 'rand()',
                    'limit' => $count,
                ]);
            } else {
                $promotionModels = PromotionModel::model()->findAllByAttributes([], [
                    'select' => '*',
                    'condition' => 'date_begin <= NOW() AND (date_end IS NULL OR date_end >= NOW())',
                    'order' => 'rand()',
                    'limit' => $count,
                ]);
            }
            if (empty($promotionModels)) {
                return [];
            }

            return $this->getFullArrayReview($promotionModels);
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get random promotions. Fatal error.', [
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить рандомные акции. Ошибка в БД.');
        }
    }

    /**
     * Создание акции пользователя
     *
     * @param integer $userId идентификатор пользователя
     * @param array $promotionSettings параметры акции
     * @param integer $promotionId идентификатор акции
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function save($userId, $promotionSettings, $promotionId = null)
    {
        if (empty($userId) || empty($promotionSettings)) {
            throw new PublicMessageException('Отсутствуют входные параметры.');
        }

        try {
            //получаем модель
            if ($promotionId !== null) {
                $promotionModel = PromotionModel::model()->findByAttributes([
                    'id' => $promotionId,
                    'user_id' => $userId,
                ]);
            }
            if (empty($promotionModel)) {
                $promotionModel = new PromotionModel;
            }
            if (empty($promotionModel)) {
                throw new PublicMessageException('Акция для данного пользователя не найдена.');
            }

            //заполняем модель
            $promotionModel->owner_user_id = $userId;
            $promotionModel->date_begin = $promotionSettings['dateBegin'];
            $promotionModel->date_end = $promotionSettings['dateEnd'];
            $promotionModel->title = $promotionSettings['title'];
            $promotionModel->text = $promotionSettings['about'];
            $promotionModel->discount = $promotionSettings['discount'];
            $promotionModel->address = $promotionSettings['address'];
            $promotionModel->discount_type = 'percent';

            //фото акции
            if (!empty($promotionSettings['photo']['tmp_name'])) {
                //перемещаем существующее фото в архив
                $this->imageHelper->archiveImage($promotionModel->image_url);
                $this->imageHelper->deleteImage($promotionModel->small_image_url);
                $promotionModel->small_image_url = $this->imageHelper->getSmallImage($promotionSettings['photo']);
                $promotionModel->image_url = $this->imageHelper->uploadImage($promotionSettings['photo']);
            }

            //определяем ближайшую станцию метро и записываем раздельно долготу и широту
            if (!empty($promotionSettings['addressCoord'])) {
                list($lat, $lng) = explode(
                    ',',
                    $promotionSettings['addressCoord']
                );
                $promotionModel->latitude = deg2rad($lat);
                $promotionModel->longitude = deg2rad($lng);
                $promotionModel->near_subway = ProfileService::getInstance()->getNearSubway($lat, $lng);
            }

            if (!$promotionModel->save()) {
                BtLogger::getLogger()->error('Can not save promotion.', [
                    'userId' => $userId,
                    'error' => $promotionModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось сохранить акцию.');
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save promotion. Fatal error.', [
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось сохранить акцию. Ошибка в БД.');
        }
    }

    /**
     * Выдает массив акций для отображения
     *
     * @param PromotionModel[] $promotionModels
     *
     * @return array
     * @throws Exception
     */
    public function getFullArrayReview(array $promotionModels)
    {
        $reviews = [];
        $i = 0;
        foreach ($promotionModels as $promotionModel) {
            $reviews[] = array_merge([
                'num' => $i++,
                'imageUrl' => !empty($promotionModel->image_url)
                    ? IMG_DIR . $promotionModel->image_url : IMG_PATH_NO_AVATAR,
                'dateBegin' => $this->dateFormatter->format($promotionModel->date_begin),
                'dateEnd' => $this->dateFormatter->format($promotionModel->date_end),
                'discountInt' => round($promotionModel->discount, 0),
            ], $promotionModel->getAttributes());
        }

        return $reviews;
    }
}
