<?php

class m190201_065216_profile_tables_update extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE master_profile ADD COLUMN youtube_url NVARCHAR(250) NULL;
            ALTER TABLE salon_profile ADD COLUMN youtube_url NVARCHAR(250) NULL;

            CREATE TABLE master_profile_direction (
                profile_id INT UNSIGNED REFERENCES profile(id), /*FOREIGN KEY*/
                direction_id INT UNSIGNED REFERENCES direction(id),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                user_id INT NOT NULL,
                CONSTRAINT fk_master_profile_direction$profile_id__profile$id 
                    FOREIGN KEY (profile_id) REFERENCES master_profile(id),
                CONSTRAINT fk_master_profile_direction$direction_id__direction$id 
                    FOREIGN KEY (direction_id) REFERENCES direction(id)    
            );
            CREATE UNIQUE INDEX idx_iu_master_profile_direction$profile_id$direction_id 
                ON master_profile_direction(profile_id, direction_id);

            CREATE TABLE master_profile_service (
                profile_id INT UNSIGNED REFERENCES profile(id),
                service_id INT UNSIGNED REFERENCES service(id),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                user_id INT NOT NULL,
                CONSTRAINT fk_master_profile_service$profile_id__profile$id 
                    FOREIGN KEY (profile_id) REFERENCES master_profile(id),
                CONSTRAINT fk_master_profile_service$service_id__service$id 
                    FOREIGN KEY (service_id) REFERENCES service(id)    
            );
            CREATE UNIQUE INDEX idx_iu_master_profile_service$profile_id$service_id 
                ON master_profile_service(profile_id, service_id);



            CREATE TABLE salon_profile_direction (
                profile_id INT UNSIGNED REFERENCES profile(id), /*FOREIGN KEY*/
                direction_id INT UNSIGNED REFERENCES direction(id),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                user_id INT NOT NULL,
                CONSTRAINT fk_salon_profile_direction$profile_id__profile$id 
                    FOREIGN KEY (profile_id) REFERENCES salon_profile(id),
                CONSTRAINT fk_salon_profile_direction$direction_id__direction$id 
                    FOREIGN KEY (direction_id) REFERENCES direction(id)    
            );
            CREATE UNIQUE INDEX idx_iu_salon_profile_direction$profile_id$direction_id 
                ON salon_profile_direction(profile_id, direction_id);

            CREATE TABLE salon_profile_service (
                profile_id INT UNSIGNED REFERENCES profile(id),
                service_id INT UNSIGNED REFERENCES service(id),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                user_id INT NOT NULL,
                CONSTRAINT fk_salon_profile_service$profile_id__profile$id 
                    FOREIGN KEY (profile_id) REFERENCES salon_profile(id),
                CONSTRAINT fk_salon_profile_service$service_id__service$id 
                    FOREIGN KEY (service_id) REFERENCES service(id)    
            );
            CREATE UNIQUE INDEX idx_iu_salon_profile_service$profile_id$service_id 
                ON salon_profile_service(profile_id, service_id);
        ');
    }

    public function safeDown()
    {
        $this->execute('   
            ALTER TABLE master_profile DROP COLUMN youtube_url;
            ALTER TABLE salon_profile DROP COLUMN youtube_url;

            DROP TABLE master_profile_direction;
            DROP TABLE master_profile_service;
            DROP TABLE salon_profile_direction;
            DROP TABLE salon_profile_service;
        ');
    }
}
