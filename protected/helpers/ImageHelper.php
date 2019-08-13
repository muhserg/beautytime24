<?php

// функция обработки ошибок
function imageHelperErrorHandler($errno, $errstr, $errfile, $errline)
{
    if ($errno === E_WARNING || $errno === E_NOTICE) {
        BtLogger::getLogger()->error('Can not work with photo. Fatal error.', [
            'errno' => $errno,
            'errstr' => $errstr,
            'errfile' => $errfile,
            'errline' => $errline,
        ]);

        /* Не запускаем внутренний обработчик ошибок PHP */
        header('Content-Type: text/html; charset=utf-8');
        echo "При сохранении изображения произошла неизвестная ошибка. Попробуйте перегрузить страницу.";
        Yii::app()->end();
    }

    return false;
}

class ImageHelper
{
    /**
     * Для конвертации в изображение
     *
     * @param string $string строка для конвертации в изображение
     * @param bool $isMobile признак мобильной верстки
     * @param string $imgType тип надписи
     *
     * @return string
     */
    static function createImageFromString($string, $isMobile = false, $imgType = ImgLabelTypeEnum::LINK)
    {
        $imLarge = null;

        if ($imgType === ImgLabelTypeEnum::LINK) {
            if ($isMobile === true) {
                $im = imagecreate(220, 20);
                $imLarge = imagecreate(2 * imagesx($im), 2 * imagesy($im));
            } else {
                $im = imagecreate(180, 20);
            }

            imagecolorallocate($im, hexdec('2F'), hexdec('32'), hexdec('43'));
            //imagecolorallocate($im, hexdec('E2'), hexdec('E2'), hexdec('E2')); // bg-bt-menu css class
            $text_color = imagecolorallocate($im, 255, 255, 255);
        } elseif ($imgType === ImgLabelTypeEnum::TEXT) {
            $im = imagecreate(100, 20);
            if ($isMobile === true) {
                $imLarge = imagecreate(2 * imagesx($im), 2 * imagesy($im));
            }
            imagecolorallocate($im, hexdec('EC'), hexdec('EC'), hexdec('EC'));
            $text_color = imagecolorallocate($im, 0, 0, 0);
        } elseif ($imgType === ImgLabelTypeEnum::LONG_TEXT) {
            $im = imagecreate(200, 20);
            if ($isMobile === true) {
                $imLarge = imagecreate(2 * imagesx($im), 2 * imagesy($im));
            }
            imagecolorallocate($im, hexdec('EC'), hexdec('EC'), hexdec('EC'));
            $text_color = imagecolorallocate($im, 0, 0, 0);
        }


        // append string to image
        if ($isMobile === true) {
            imagestring($im, 4, 0, 0, $string, $text_color);
            imagecopyresampled(
                $imLarge,
                $im,
                0, 0, 0, 0,
                2 * imagesx($im),
                2 * imagesy($im),
                imagesx($im),
                imagesy($im)
            );
            $im = $imLarge;
        } else {
            imagestring($im, 4, 0, 0, $string, $text_color);
        }

        ob_start();
        imagepng($im);
        $data = ob_get_contents();
        ob_end_clean();

        return 'data:image/png;base64,' . base64_encode($data);
    }

    /**
     * Загрузка изображения на сервер
     *
     * @param array $arFile Данные загруженного изображения из $_FILES
     * @param string $uploadDir Директория загрузки
     * @return string Имя созданного файла на сайте
     *
     * @throws PublicMessageException
     */
    public function uploadImage($arFile, $uploadDir = IMG_UPLOAD_DIR)
    {
        // переключаемся на пользовательский обработчик
        $oldErrorHandler = set_error_handler("imageHelperErrorHandler");

        $tmpPhotoFile = $arFile['tmp_name'];
        $imageInfo = getimagesize($tmpPhotoFile);
        if (!in_array($imageInfo['mime'], $this->getValidImageFileTypes(), true)) {
            throw new PublicMessageException('Изображение неверного типа. Загрузка не возможна.');
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir);
        }
        $newImgFileName = md5(rand() . time()) . '.' . str_replace('image/', '', $imageInfo['mime']);
        if (!move_uploaded_file($tmpPhotoFile, $uploadDir . $newImgFileName)) {
            throw new PublicMessageException('Не удалось сохранить фото на сервере.');
        }

        //возращаем исходный обработчик
        set_error_handler($oldErrorHandler);
        return $newImgFileName;
    }

    function resizePhoto($file, $newFileNameWithPath, $quality = 90, $wMax = IMG_SMALL_WIDTH)
    {
        // переключаемся на пользовательский обработчик
        $oldErrorHandler = set_error_handler("imageHelperErrorHandler");

        // Cоздаём исходное изображение на основе исходного файла
        if ($file['type'] == 'image/jpeg') {
            $source = @imagecreatefromjpeg($file['tmp_name']);
        } elseif ($file['type'] == 'image/png') {
            $source = imagecreatefrompng($file['tmp_name']);
        } elseif ($file['type'] == 'image/gif') {
            $source = imagecreatefromgif($file['tmp_name']);
        } else {
            return false;
        }

        // Определяем ширину и высоту изображения
        $widthSrc = imagesx($source);
        $heightSrc = imagesy($source);

        if ($widthSrc > $wMax) {
            // Вычисление пропорций
            $ratio = $widthSrc / $wMax;
            $widthDest = round($widthSrc / $ratio);
            $heightDest = round($heightSrc / $ratio);

            // Создаём пустую картинку
            $dest = imagecreatetruecolor($widthDest, $heightDest);

            // Копируем старое изображение в новое с изменением параметров
            imagecopyresampled($dest, $source, 0, 0, 0, 0, $widthDest, $heightDest, $widthSrc, $heightSrc);

            imagejpeg($dest, $newFileNameWithPath, $quality);
            imagedestroy($dest);

            return true;
        }

        imagejpeg($source, $newFileNameWithPath, $quality);
        imagedestroy($source);

        //возращаем исходный обработчик
        set_error_handler($oldErrorHandler);
        return true;
    }

    /**
     * Конвертирует маленькое изображение из большого и сохраняет на сервер
     *
     * @param array $arFile Данные загруженного изображения из $_FILES
     * @param string $uploadDir Директория загрузки уменьшенных фото
     * @return string Имя созданного файла на сайте
     *
     * @throws PublicMessageException
     */
    public function getSmallImage($arFile, $uploadDir = IMG_SMALL_UPLOAD_DIR)
    {
        $tmpPhotoFile = $arFile['tmp_name'];
        $imageInfo = getimagesize($tmpPhotoFile);
        if (!in_array($imageInfo['mime'], $this->getValidImageFileTypes(), true)) {
            throw new PublicMessageException('Изображение неверного типа. Загрузка не возможна.');
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir);
        }

        $newImgFileName = md5(rand() . time()) . '.' . str_replace('image/', '', $imageInfo['mime']);
        if (!$this->resizePhoto($arFile, $uploadDir . $newImgFileName)) {
            throw new PublicMessageException('Не удалось уменьшить и сохранить фото на сервере.');
        }

        return $newImgFileName;
    }

    /**
     * Возвращает допустимые типы данных для фото
     *
     * @return array
     */
    public function getValidImageFileTypes()
    {
        return ['image/gif', 'image/jpeg', 'image/png'];
    }


    /**
     * Перемещение в архивную папку
     * @param string $photoFileName Имя устаревшего фото
     * @param string $uploadDir Директория загрузки
     * @param string $archiveDir Директория архивации страых фото
     * @return bool
     *
     * @throws PublicMessageException
     */
    public function archiveImage($photoFileName, $uploadDir = IMG_UPLOAD_DIR, $archiveDir = OLD_IMG_UPLOAD_DIR)
    {
        if (!empty($photoFileName)) {
            if (!is_dir($archiveDir)) {
                mkdir($archiveDir);
            }
            //на всякий случай - очищаем значение поля avatar_file_name для безопасности
            $oldPhotoFileName = preg_replace('/[^\da-z\.]/i', '', $photoFileName);
            if (file_exists($uploadDir . $oldPhotoFileName) === true) {
                if (rename($uploadDir . $oldPhotoFileName, $archiveDir . $oldPhotoFileName) === false) {
                    throw new PublicMessageException('Не удалось сохранить фото в профиле.');
                }
            }
        }

        return true;
    }

    /**
     * Удаление картинки
     *
     * @param string $photoFileName Имя устаревшего фото
     * @param string $uploadDir Директория загрузки
     *
     * @return bool
     * @throws PublicMessageException
     */
    public function deleteImage($photoFileName, $uploadDir = IMG_SMALL_UPLOAD_DIR)
    {
        if (!empty($photoFileName)) {
            //на всякий случай - очищаем значение поля avatar_file_name для безопасности
            $oldPhotoFileName = preg_replace('/[^\da-z\.]/i', '', $photoFileName);

            if (file_exists($uploadDir . $oldPhotoFileName) === true) {
                if (unlink($uploadDir . $oldPhotoFileName) === false) {
                    throw new PublicMessageException('Не удалось удалить фото в профиле.');
                }
            }
        }

        return true;
    }
}
