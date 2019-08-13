<?php

class m190118_082244_user extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE user (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                login NVARCHAR(250) NOT NULL,
                pass CHAR(32) NOT NULL,
                type INT NOT NULL,
                phone NVARCHAR(100) NOT NULL,
                email NVARCHAR(100) NOT NULL,
                first_name NVARCHAR(100) NULL,
                middle_name NVARCHAR(100) NULL,
                last_name NVARCHAR(100) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                email_code CHAR(32) NULL,
                phone_code INT NULL,
                confirm_email BOOL DEFAULT false NULL,
                confirm_phone BOOL DEFAULT false NULL
            );
            
            CREATE UNIQUE INDEX idx_user$login ON user(login);
            CREATE UNIQUE INDEX idx_user$phone ON user(phone);
            CREATE UNIQUE INDEX idx_user$email ON user(email);

            CREATE TABLE user_type (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name NVARCHAR(100) NOT NULL,
                rus_name NVARCHAR(100) NOT NULL
            );
            
            CREATE UNIQUE INDEX idx_user_type$name ON user_type(name);
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE user;
            DROP TABLE user_type;
        ');
    }
}