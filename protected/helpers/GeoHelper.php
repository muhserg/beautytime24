<?php


class GeoHelper
{
    /** @var int радиус Земли (в метрах) */
    public $earthRadius = 6378137;

    /**
     * Расстояние между двумя точками (в метрах)
     *
     * @param float $lat1 широта первой точки
     * @param float $lng1 долгота первой точки
     * @param float $lat2 широта первой точки
     * @param float $lng2 долгота первой точки
     *
     * @return string
     */
    public function distance($lat1, $lng1, $lat2, $lng2)
    {
        // Convert degrees to radians.
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        return round($this->earthRadius * acos(
                cos($lat1) * cos($lat2) * cos($lng2 - $lng1) + sin($lat1) * sin($lat2)
            )
        );
    }

    /**
     * Форматирование расстояния
     *
     * @param integer $distance в метрах
     *
     * @return string
     */
    public function formatter($distance)
    {
        if ($distance <= 1000) {
            return $distance . ' м';
        } elseif ($distance > 1000) {
            return round($distance / 1000, 1) . ' км';
        }
    }
}
