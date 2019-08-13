<?php

class m190412_223251_salon_portfolio extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE salon_portfolio (
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
            DROP TABLE salon_portfolio;
        ');
    }
}
