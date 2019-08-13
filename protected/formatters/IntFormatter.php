<?php

/**
 * форматирование Integer
 */
class IntFormatter
{
    /**
     * Формирование чисел
     *
     * @param string $param
     *
     * @return string
     */
    public function format($param)
    {
        return IntVal($param);
    }
}
