<?php

class m190311_063446_promotion extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE promotion (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                owner_user_id INT UNSIGNED REFERENCES user(id),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                date_begin DATETIME NOT NULL,
                date_end DATETIME NULL,
                title NVARCHAR(250) NOT NULL,
                text NVARCHAR(2000) NOT NULL,
                url NVARCHAR(250) NULL,
                address NVARCHAR(300) NULL,
                image_url NVARCHAR(250) NULL,
                small_image_url NVARCHAR(250) NULL,
                discount NUMERIC(5,2) NULL,
                discount_type NVARCHAR(30) NULL,
                latitude DOUBLE NULL,
                longitude DOUBLE NULL,
                near_subway NVARCHAR(100) NULL,
                
                CONSTRAINT fk_user_promotion$owner_user_id$id 
                    FOREIGN KEY (owner_user_id) REFERENCES user(id)
            );
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE promotion;
        ');
    }
}
