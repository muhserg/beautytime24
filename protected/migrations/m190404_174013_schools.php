<?php

class m190404_174013_schools extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE school (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                owner_user_id INT UNSIGNED REFERENCES user(id),
                name NVARCHAR(250) NULL,
                phone NVARCHAR(30) NULL,
                email NVARCHAR(250) NULL,
                rating FLOAT NULL,
                about NVARCHAR(2000) NULL,
                address NVARCHAR(300) NOT NULL,
                address_coord NVARCHAR(60) NULL,
                latitude DOUBLE NULL,
                longitude DOUBLE NULL,
                near_subway NVARCHAR(100) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                
                CONSTRAINT fk_school$owner_user_id$id 
                    FOREIGN KEY (owner_user_id) REFERENCES user(id)
            );
            CREATE INDEX idx_in_school$owner_user_id
                ON school(owner_user_id);
            CREATE INDEX idx_in_school$near_subway
                ON school(near_subway);

            CREATE TABLE school_direction (
                school_id INT UNSIGNED REFERENCES school(id), 
                direction_id INT UNSIGNED REFERENCES direction(id),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                CONSTRAINT fk_school_direction$school_id__school$id 
                    FOREIGN KEY (school_id) REFERENCES school(id),
                CONSTRAINT fk_school_direction$direction_id__direction$id 
                    FOREIGN KEY (direction_id) REFERENCES direction(id)    
            );
            CREATE UNIQUE INDEX idx_iu_school_direction$school_id$direction_id 
                ON school_direction(school_id, direction_id);

            CREATE TABLE school_course (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                school_id INT UNSIGNED REFERENCES school(id),
                name NVARCHAR(250) NULL,
                duration FLOAT NULL,
                cost NUMERIC(10,2) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                CONSTRAINT fk_school_course$school_id__school$id 
                    FOREIGN KEY (school_id) REFERENCES school(id)  
            );
            CREATE INDEX idx_in_school_course$school_id
                ON school_course(school_id);


            CREATE TABLE study_video (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                owner_user_id INT UNSIGNED REFERENCES user(id),
                direction_id INT UNSIGNED REFERENCES direction(id),
                title NVARCHAR(255) NOT NULL,
                link NVARCHAR(500) NOT NULL,
                description NVARCHAR(255) NOT NULL,
                
                CONSTRAINT fk_study_video$owner_user_id$id 
                    FOREIGN KEY (owner_user_id) REFERENCES user(id)
            );
            CREATE INDEX idx_in_study_video$owner_user_id
                ON study_video(owner_user_id);
            CREATE INDEX idx_in_study_video$direction_id 
                ON study_video(direction_id);
            CREATE INDEX idx_in_study_video$title 
                ON study_video(title);
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE school_course;
            DROP TABLE school_direction;
            DROP TABLE school;
            DROP TABLE study_video;
        ');
    }
}
