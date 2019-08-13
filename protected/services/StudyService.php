<?php

/**
 * Сервис для обучения мастеров
 */
class StudyService
{
    private static $studyService = null;

    /** var DateFormatter */
    private $dateFormatter;

    /** @var \CDbConnection */
    private $db;

    /**
     * @return StudyService
     */
    public static function getInstance()
    {
        if (self::$studyService === null) {
            self::$studyService = new self();
        }
        return self::$studyService;
    }

    public function __construct()
    {
        $this->db = Yii::app()->db;
        $this->dateFormatter = new DateFormatter();
    }


    /**
     * Список обучающих видео
     *
     * @param integer $userId идентификатор пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getVideos($userId)
    {
        try {
            $videoStudies = StudyVideoModel::model()->findAllByAttributes([
                'owner_user_id' => $userId,
            ], [
                'order' => 'created_at ASC',
            ]);

            return $videoStudies;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get study videos. Fatal error.', [
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось получить список видео. Ошибка в БД.');
        }
    }

    /**
     * Сохранение ссылки на видео обучение
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     * @param array $videoSettings видео ссылки
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function saveVideo($userId, $userType, $videoSettings)
    {
        if (empty($videoSettings) || empty($videoSettings['link'])) {
            throw new PublicMessageException('Отсутствуют входные параметры.');
        }
        if (empty($userId)) {
            throw new PublicMessageException('Отсутствует пользователь.');
        }
        if (strpos($videoSettings['link'], StudyVideoEnum::YOUTUBE_LINK_PREFIX) === false) {
            throw new PublicMessageException(
                'Видео ссылка должна начинаться на "' . StudyVideoEnum::YOUTUBE_LINK_PREFIX . '"'
            );
        }


        try {
            //получаем модель
            $studyVideoModel = new StudyVideoModel;

            //заполняем модель
            $studyVideoModel->owner_user_id = $userId;
            $studyVideoModel->direction_id = $videoSettings['directionId'];
            $studyVideoModel->title = $videoSettings['title'];
            $studyVideoModel->link = $videoSettings['link'];
            $studyVideoModel->description = $videoSettings['description'];

            if (!$studyVideoModel->save()) {
                BtLogger::getLogger()->error('Can not save study video.', [
                    'scheduleSettings' => $videoSettings,
                    'error' => $studyVideoModel->getErrors(),
                ]);

                throw new PublicMessageException('Не удалось сохранить видео обучения.');
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save study video. Fatal error.', [
                'userId' => $userId,
                'videoSettings' => $videoSettings,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось сохранить видео обучения. Ошибка в БД.');
        }
    }

    /**
     * Удаление видео из обучения
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     * @param integer $videoId идентификатор видео
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function deleteVideo($userId, $userType, $videoId)
    {
        try {
            $studyVideoModel = StudyVideoModel::model()->findByAttributes([
                'id' => $videoId,
                'owner_user_id' => $userId,
            ]);

            if ($studyVideoModel === null) {
                throw new PublicMessageException('Не удалось удалить видео.');
            }

            return $studyVideoModel->delete();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not delete study video. Fatal error.', [
                'photoId' => $videoId,
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось удалить видео. Ошибка в БД.');
        }
    }
}
