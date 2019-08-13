<?php

class m190128_170535_profile_tables extends CDbMigration
{
    public function Up()
    {
        $this->execute('
            ALTER TABLE user DROP COLUMN first_name;
            ALTER TABLE user DROP COLUMN last_name;
            ALTER TABLE user DROP COLUMN middle_name;

            CREATE TABLE client_profile (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                first_name NVARCHAR(100) NOT NULL,
                last_name NVARCHAR(100) NOT NULL,
                middle_name NVARCHAR(100) NULL,
                user_id INT NOT NULL,
                avatar_file_name NVARCHAR(40) NULL,
                small_avatar_file_name NVARCHAR(40) NULL,
                address NVARCHAR(300) NOT NULL,
                address_coord NVARCHAR(60) NULL,
                fio_address_hash CHAR(32) NOT NULL,
                about NVARCHAR(2000) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            );
            
            CREATE UNIQUE INDEX idx_ui_client_profile$fio_address_hash ON client_profile(fio_address_hash);
            CREATE UNIQUE INDEX idx_ui_client_profile$user_id ON client_profile(user_id);

            CREATE TABLE master_profile (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                first_name NVARCHAR(100) NOT NULL,
                last_name NVARCHAR(100) NOT NULL,
                middle_name NVARCHAR(100) NULL,
                user_id INT NOT NULL,
                avatar_file_name NVARCHAR(40) NULL,
                small_avatar_file_name NVARCHAR(40) NULL,
                address NVARCHAR(300) NULL,
                address_coord NVARCHAR(60) NULL,
                work_address NVARCHAR(300) NULL,
                work_address_coord NVARCHAR(60) NULL,
                about NVARCHAR(2000) NULL,
                work_experience FLOAT NOT NULL,
                fio_address_hash CHAR(32) NOT NULL,
                rating FLOAT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            );
            
            CREATE UNIQUE INDEX idx_ui_master_profile$fio_address_hash ON master_profile(fio_address_hash);
            CREATE UNIQUE INDEX idx_ui_master_profile$user_id ON master_profile(user_id);

            CREATE TABLE salon_profile (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name NVARCHAR(250) NOT NULL,
                ur_name NVARCHAR(250) NOT NULL,
                inn NVARCHAR(12) NOT NULL,
                ogrn CHAR(13) NULL,
                gendir_first_name NVARCHAR(100) NULL,
                gendir_last_name NVARCHAR(100) NULL,
                gendir_middle_name NVARCHAR(100) NULL,
                user_id INT NOT NULL,
                logo_file_name NVARCHAR(40) NULL,
                photo_inside_file_name NVARCHAR(40) NULL,
                ur_address NVARCHAR(300) NULL,
                ur_address_coord NVARCHAR(60) NULL,
                address NVARCHAR(300) NULL,
                address_coord NVARCHAR(60) NULL,
                description NVARCHAR(2000) NULL,
                rating FLOAT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            );
            
            CREATE UNIQUE INDEX idx_ui_salon_profile$user_id ON salon_profile(user_id);
            CREATE UNIQUE INDEX idx_ui_salon_profile$inn ON salon_profile(inn);
        ');


    }

    public function Down()
    {
        $this->execute('
            DROP TABLE client_profile;
            DROP TABLE master_profile;
            DROP TABLE salon_profile;

            ALTER TABLE user ADD COLUMN first_name NVARCHAR(100);
            ALTER TABLE user ADD COLUMN last_name NVARCHAR(100);
            ALTER TABLE user ADD COLUMN middle_name NVARCHAR(100);
        ');
    }
}
