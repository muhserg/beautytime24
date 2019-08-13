<?php


class PhoneHelper
{
    /**
     * Форматирование телефонного номера для сохранения
     *
     * @param string $phone телефонный номер
     *
     * @return string
     */
    public function formatPhoneForSave($phone)
    {
        return preg_replace("/[^0-9]/", '', $phone);
    }

    /**
     * Форматирование телефонного номера для отображения
     *
     * @param string $phone телефонный номер
     *
     * @return string
     */
    public function formatPhoneForView($phone)
    {
        if (preg_match('/^7(\d{3})(\d{3})(\d{2})(\d{2})$/', $phone, $matches)) {
            $result = '+7(' . $matches[1] . ')' . $matches[2] . '-' . $matches[3] . '-' . $matches[4];
            return $result;
        }

        return $phone;
    }
}
