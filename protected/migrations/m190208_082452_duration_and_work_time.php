<?php

class m190208_082452_duration_and_work_time extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE work_schedule (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED REFERENCES user(id),
                profile_id INT UNSIGNED REFERENCES profile(id),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                weekdays NVARCHAR(100) NOT NULL,
                time_begin TIME NOT NULL,
                time_end TIME NOT NULL
            );
            CREATE INDEX idx_in_work_schedule$user_id$profile_id 
                ON work_schedule(user_id, profile_id);

            ALTER TABLE master_profile_service ADD COLUMN duration FLOAT NULL;
            ALTER TABLE master_profile_service ADD COLUMN cost NUMERIC(10,2) NULL;

            ALTER TABLE salon_profile_service ADD COLUMN duration FLOAT NULL;
            ALTER TABLE salon_profile_service ADD COLUMN cost NUMERIC(10,2) NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE work_schedule;

            ALTER TABLE master_profile_service DROP COLUMN duration;
            ALTER TABLE master_profile_service DROP COLUMN cost;

            ALTER TABLE salon__profile_service DROP COLUMN duration;
            ALTER TABLE salon_profile_service DROP COLUMN cost;
        ');
    }
}