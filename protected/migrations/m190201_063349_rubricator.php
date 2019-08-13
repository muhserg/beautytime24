<?php

class m190201_063349_rubricator extends CDbMigration
{
    public function Up()
    {
        $this->execute('
            CREATE TABLE direction (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name NVARCHAR(250) NOT NULL,
                eng_name NVARCHAR(250) NULL,
                description NVARCHAR(2000) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            );

            CREATE TABLE service (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                direction_id INT UNSIGNED, 
                name NVARCHAR(250) NOT NULL,
                eng_name NVARCHAR(250) NULL,
                description NVARCHAR(2000) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                CONSTRAINT fk_service$id__direction$id 
                    FOREIGN KEY (direction_id) REFERENCES direction(id)
            );
        ');
    }

    public function Down()
    {
        $this->execute('
            DROP TABLE direction;
            DROP TABLE service;
        ');
    }
}
