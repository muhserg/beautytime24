<?php

class m190130_160124_master_portfolio extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE master_portfolio (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                work_photo_file_name NVARCHAR(40) NULL,
                about NVARCHAR(2000) NOT NULL
            );
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE master_portfolio;
        ');
    }
}
