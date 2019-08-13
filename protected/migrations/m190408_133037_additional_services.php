<?php

class m190408_133037_additional_services extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE master_additional_service (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                profile_id INT UNSIGNED REFERENCES profile(id),
                parent_service_id INT UNSIGNED REFERENCES service(id),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                name NVARCHAR(255) NOT NULL,
                duration FLOAT NULL,
                cost NUMERIC(10,2) NULL,
                user_id INT NOT NULL,
                CONSTRAINT fk_master_additional_service$profile_id__profile$id 
                    FOREIGN KEY (profile_id) REFERENCES master_profile(id),
                CONSTRAINT fk_master_additional_service$parent_service_id__service$id 
                    FOREIGN KEY (parent_service_id) REFERENCES service(id)    
            );
            CREATE INDEX idx_in_master_additional_service$profile_id$parent_service_id 
                ON master_additional_service(profile_id, parent_service_id);

            CREATE TABLE salon_additional_service (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                profile_id INT UNSIGNED REFERENCES profile(id),
                parent_service_id INT UNSIGNED REFERENCES service(id),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                name NVARCHAR(255) NOT NULL,
                duration FLOAT NULL,
                cost NUMERIC(10,2) NULL,
                user_id INT NOT NULL,
                CONSTRAINT fk_salon_additional_service$profile_id__profile$id 
                    FOREIGN KEY (profile_id) REFERENCES master_profile(id),
                CONSTRAINT fk_salon_additional_service$parent_service_id__service$id 
                    FOREIGN KEY (parent_service_id) REFERENCES service(id)    
            );
            CREATE INDEX idx_in_salon_additional_service$profile_id$parent_service_id 
                ON salon_additional_service(profile_id, parent_service_id);
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE master_additional_service;
            DROP TABLE salon_additional_service;
        ');
    }
}
