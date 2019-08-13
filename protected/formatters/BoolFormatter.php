<?php

/**
 * форматирование boolean
 */
class BoolFormatter
{
    /**
     * Формирование true/false
     *
     * @param string $param
     *
     * @return string
     */
    public function format($param)
    {
        return (!empty($param) && ($param === '1' || $param === true) ? true : false);
    }
}
