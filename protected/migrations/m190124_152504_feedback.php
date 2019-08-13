<?php

class m190124_152504_feedback extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE feedback (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sender_name NVARCHAR(250) NOT NULL,
                phone NVARCHAR(100) NULL,
                email NVARCHAR(100) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                user_id INT NULL,
                comment NVARCHAR(2000) NOT NULL
            );
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE feedback;
        ');
    }
}
