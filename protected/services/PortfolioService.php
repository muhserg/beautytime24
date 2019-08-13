<?php

/**
 * Обеспечивает работу с портфолио пользователя
 */
class PortfolioService
{
    private static $portfolioService = null;

    /** var ImageHelper */
    private $imageHelper;

    /**
     * @return PortfolioService
     */
    public static function getInstance()
    {
        if (self::$portfolioService === null) {
            self::$portfolioService = new self();
        }
        return self::$portfolioService;
    }

    public function __construct()
    {
        $this->imageHelper = new ImageHelper();
    }

    /**
     * Сохранение профиля пользователя
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     * @param array $profileSettings параметры портфолио пользователя
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function savePortfolio($userId, $userType, $profileSettings)
    {
        if (empty($profileSettings)) {
            throw new PublicMessageException('Отсутствуют входные параметры.');
        }

        if ($userType === UserTypeEnum::CLIENT) {
            throw new PublicMessageException('Для данного пользователя нельзя добавить портфолио.');
        }

        try {
            //получаем модель
            if ($userType === UserTypeEnum::MASTER) {
                $poftfolioModel = new MasterPortfolioModel;
            } elseif ($userType === UserTypeEnum::SALON) {
                $poftfolioModel = new SalonPortfolioModel;
            } else {
                throw new PublicMessageException('Неизвестный тип пользователя для порфолио.');
            }

            if ($poftfolioModel === null) {
                throw new PublicMessageException('Тип профиля не найден.');
            }

            //заполняем модель
            $poftfolioModel->user_id = $userId;
            $poftfolioModel->about = $profileSettings['about'];

            //фото работы мастера
            if (!empty($profileSettings['photo']['tmp_name'])) {
                //перемещаем существующее фото в архив
                $this->imageHelper->archiveImage(
                    $poftfolioModel->work_photo_file_name,
                    IMG_PORTFOLIO_UPLOAD_DIR,
                    OLD_IMG_PORTFOLIO_UPLOAD_DIR
                );
                $poftfolioModel->work_photo_file_name = $this->imageHelper->uploadImage(
                    $profileSettings['photo'],
                    IMG_PORTFOLIO_UPLOAD_DIR
                );
            }

            if (!$poftfolioModel->save()) {
                BtLogger::getLogger()->error('Can not save profile.', [
                    'profileSettings' => $profileSettings,
                    'error' => $poftfolioModel->getErrors(),
                ]);

                if (!empty($profileSettings['photo']['tmp_name'])) {
                    $this->imageHelper->archiveImage(
                        $poftfolioModel->work_photo_file_name,
                        IMG_PORTFOLIO_UPLOAD_DIR,
                        OLD_IMG_PORTFOLIO_UPLOAD_DIR
                    );
                }
                throw new PublicMessageException('Не удалось сохранить портфолио.');
            }

            return true;
        } catch (PublicMessageException $e) {
            throw new PublicMessageException($e->getMessage());
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not save portfolio. Fatal error.', [
                'profileSettings' => $profileSettings,
                'photoData' => isset($profileSettings['photo']) ? $profileSettings['photo'] : null,
                'error' => $e,
            ]);
            if (!empty($profileSettings['photo']['tmp_name'])) {
                $this->imageHelper->archiveImage(
                    $poftfolioModel->work_photo_file_name,
                    IMG_PORTFOLIO_UPLOAD_DIR,
                    OLD_IMG_PORTFOLIO_UPLOAD_DIR
                );
            }

            throw new PublicMessageException('Не удалось сохранить портфолио. Ошибка в БД.');
        }
    }

    /**
     * Получение портфолио пользователя
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     *
     * @return array
     * @throws PublicMessageException
     */
    public function getPortfolio($userId, $userType)
    {
        $workPhotos = [];

        try {
            if ($userType === UserTypeEnum::MASTER) {
                $workPhotoModels = MasterPortfolioModel::model()->findAllByAttributes(['user_id' => $userId]);
            } elseif ($userType === UserTypeEnum::SALON) {
                $workPhotoModels = SalonPortfolioModel::model()->findAllByAttributes(['user_id' => $userId]);
            } else {
                throw new PublicMessageException('Неизвестный тип пользователя для порфолио.');
            }

            if (!empty($workPhotoModels)) {
                foreach ($workPhotoModels as $workPhotoModel) {
                    $workPhotos[] = [
                        'id' => $workPhotoModel->id,
                        'photoUrl' => IMG_PORTFOLIO_DIR . $workPhotoModel->work_photo_file_name,
                        'about' => $workPhotoModel->about,
                    ];
                }
            }

            return $workPhotos;
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not get portfolio. Fatal error.', [
                'workPhotos' => $workPhotos,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось загрузить портфолио. Ошибка в БД.');
        }
    }

    /**
     * Удаление фото из портфолио
     *
     * @param integer $userId идентификатор пользователя
     * @param string $userType тип пользователя
     * @param integer $photoId идентификатор фото в портфолио
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function deletePhoto($userId, $userType, $photoId)
    {
        try {
            if ($userType === UserTypeEnum::MASTER) {
                $portfolioModel = MasterPortfolioModel::model()->findByAttributes([
                    'id' => $photoId,
                    'user_id' => $userId,
                ]);
            } elseif ($userType === UserTypeEnum::SALON) {
                $portfolioModel = SalonPortfolioModel::model()->findByAttributes([
                    'id' => $photoId,
                    'user_id' => $userId,
                ]);
            } else {
                throw new PublicMessageException('Неизвестный тип пользователя для портфолио.');
            }

            if ($portfolioModel === null) {
                throw new PublicMessageException('Не удалось удалить фото.');
            }

            return $portfolioModel->delete();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Can not delete portfolio. Fatal error.', [
                'photoId' => $photoId,
                'userId' => $userId,
                'error' => $e,
            ]);

            throw new PublicMessageException('Не удалось удалить фото. Ошибка в БД.');
        }
    }
}
