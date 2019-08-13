<?php

class m190301_072635_vacancy extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE master_profile ADD is_vacancy BOOLEAN NULL;

            CREATE TABLE vacancy (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                salon_user_id INT UNSIGNED REFERENCES user(id),
                profession_id INT UNSIGNED NOT NULL,
                work_experience INT NOT NULL,
                work_place_photo NVARCHAR(40) NULL,
                description NVARCHAR(2000) NOT NULL,
                salary FLOAT NULL,
                rating FLOAT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                
                CONSTRAINT fk_user_vacancy$salon_user_id$id 
                    FOREIGN KEY (salon_user_id) REFERENCES user(id)
            );

            CREATE TABLE vacancy_schedule (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                salon_user_id INT UNSIGNED REFERENCES user(id),
                vacancy_id INT UNSIGNED REFERENCES vacancy(id),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                weekdays NVARCHAR(100) NOT NULL,
                time_begin TIME NOT NULL,
                time_end TIME NOT NULL,
                
                CONSTRAINT fk_user_vacancy_schedule$salon_user_id$id 
                    FOREIGN KEY (salon_user_id) REFERENCES user(id)
            );
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE master_profile DROP COLUMN is_vacancy;
            DROP TABLE vacancy;
            DROP TABLE vacancy_schedule;
        ');
    }
}
