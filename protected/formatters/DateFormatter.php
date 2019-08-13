<?php

/**
 * форматирование даты
 */
class DateFormatter
{
    /**
     * Формирование даты
     *
     * @param string $date
     *
     * @return string
     * @throws Exception
     */
    public function format($date)
    {
        //пустая строка  вместо null из-за особенности работы twig
        return ($date !== null ? (new DateTime($date))->format('d.m.Y') : '');
    }

    /**
     * Формирование даты и времени
     *
     * @param string $date
     *
     * @return string
     * @throws Exception
     */
    public function formatDateTime($date)
    {
        //пустая строка  вместо null из-за особенности работы twig
        return ($date !== null ?
            (new DateTime($date, new DateTimeZone('Europe/Moscow')))->format('d.m.Y H:i') : '');
    }
}
