<?php

class m190222_145324_hits_sessions extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE session (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                created_at DATETIME NOT NULL,
                session_php_id CHAR(26) NOT NULL,
                user_id INT UNSIGNED REFERENCES user(id),
                uri_begin NVARCHAR(256) NOT NULL,
                user_agent NVARCHAR(256) NULL,
                
                CONSTRAINT fk_user$user_id$id 
                    FOREIGN KEY (user_id) REFERENCES user(id)
            );

            CREATE UNIQUE INDEX idx_ui_session$session_php_id ON session(session_php_id);

            CREATE TABLE hit (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                created_at DATETIME NOT NULL,
                session_db_id INT UNSIGNED NOT NULL REFERENCES session(id),   
                user_id INT UNSIGNED REFERENCES user(id),
                uri NVARCHAR(256) NOT NULL,
                user_agent NVARCHAR(256) NULL,
        
                CONSTRAINT fk_session$session_db_id$id 
                    FOREIGN KEY (session_db_id) REFERENCES session(id)
            );
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE hit;
            DROP TABLE session;
        ');
    }
}
