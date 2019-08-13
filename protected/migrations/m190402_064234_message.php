<?php

class m190402_064234_message extends CDbMigration
{
    // Use safeUp/safeDown to do migration with transaction
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE message (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                from_user_id INT UNSIGNED REFERENCES user(id),
                title NVARCHAR(255) NULL,
                file NVARCHAR(255) NULL,
                message NVARCHAR(2000) NOT NULL,
                is_moderated BOOLEAN NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                
                CONSTRAINT fk_message$from_user_id$id 
                    FOREIGN KEY (from_user_id) REFERENCES user(id)
            );
            CREATE INDEX idx_in_message$from_user_id
                ON message(from_user_id);


            CREATE TABLE users_message (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                message_id INT UNSIGNED REFERENCES message(id),
                from_user_id INT UNSIGNED REFERENCES user(id),
                to_user_id INT UNSIGNED REFERENCES user(id),
                to_user_type_name NVARCHAR(100) NOT NULL,
                
                CONSTRAINT fk_users_message$message_id$id 
                    FOREIGN KEY (message_id) REFERENCES message(id),
                CONSTRAINT fk_users_message$from_user_id$id 
                    FOREIGN KEY (from_user_id) REFERENCES user(id),
                CONSTRAINT fk_users_message$to_user_id$id 
                    FOREIGN KEY (to_user_id) REFERENCES user(id)
            );
            CREATE INDEX idx_in_users_message$message_id
                ON users_message(message_id);
            CREATE INDEX idx_in_users_message$from_user_id
                ON users_message(from_user_id);
            CREATE INDEX idx_in_users_message$to_user_id
                ON users_message(to_user_id);
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE users_message;
            DROP TABLE message;
        ');
    }
}
