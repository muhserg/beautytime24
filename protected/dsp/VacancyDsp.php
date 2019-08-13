<?php

/**
 * Вакансии
 */
class VacancyDsp
{
    /** @var \CDbConnection */
    private $db;

    public function __construct()
    {
        $this->db = Yii::app()->db;
    }

    /**
     * Возвращает имя таблицы
     * @return string
     */
    public function getTableName()
    {
        return TableNameEnum::VACANCY;
    }

    /**
     * Возвращает число вакансий
     *
     * @return integer
     * @throws CException
     */
    public function getCounts()
    {
        $sql = "
            SELECT 
                count(v.id) AS vacancy_count 
            FROM " . $this->getTableName() . " v
        ";
        return $this->db->createCommand($sql)->queryScalar([]);
    }

}
