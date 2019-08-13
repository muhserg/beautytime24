<?php

/**
 * обрезание строки на определенное кол-во символов
 */
class ShortFormatter
{
    /**
     * Формирование даты
     *
     * @param string $date
     *
     * @return string
     * @throws Exception
     */
    public function format($string)
    {
        //пустая строка вместо null из-за особенности работы twig
        return ($string !== null ? mb_strimwidth($string, 0, STRING_STRIM_WIDTH) : '');
    }
}
