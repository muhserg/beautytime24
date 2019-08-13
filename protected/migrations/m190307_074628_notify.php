<?php

class m190307_074628_notify extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE notify (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED REFERENCES user(id),
                type NVARCHAR(15) NOT NULL,
                radius float UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
        
                CONSTRAINT fk_user_notify$user_id$id 
                    FOREIGN KEY (user_id) REFERENCES user(id)
            );
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE notify;
        ');
    }
}
