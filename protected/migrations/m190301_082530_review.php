<?php

class m190301_082530_review extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE orders ADD status INT NOT NULL DEFAULT 0;

            CREATE TABLE review (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED REFERENCES user(id),
                order_id INT UNSIGNED NULL,
                assessment INT NULL,
                text NVARCHAR(2000) NOT NULL,
                is_moderated BOOLEAN NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                
                CONSTRAINT fk_user_review$user_id$id 
                    FOREIGN KEY (user_id) REFERENCES user(id)
            );
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE orders DROP COLUMN status;
            DROP TABLE review;
        ');
    }
}
