<?php

class m190215_102134_order extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE orders (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                receipt_date DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                main_service_id INT UNSIGNED NOT NULL REFERENCES service(id),
                owner_user_id INT UNSIGNED NOT NULL REFERENCES user(id),
                owner_user_type_id INT UNSIGNED NOT NULL REFERENCES user_type(id),
                master_profile_id INT UNSIGNED REFERENCES master_profile(id),
                salon_profile_id INT UNSIGNED REFERENCES salon_profile(id),
                total NUMERIC(10,2) NOT NULL,
                plan_duration INT NULL,
                fact_duration INT NULL,
                rating INT NULL,
                CONSTRAINT fk_service$main_service_id$id 
                    FOREIGN KEY (main_service_id) REFERENCES service(id),
                CONSTRAINT fk_user$owner_user_id$id 
                    FOREIGN KEY (owner_user_id) REFERENCES user(id),
                CONSTRAINT fk_user_type$owner_user_type_id$id 
                    FOREIGN KEY (owner_user_type_id) REFERENCES user_type(id),  
                CONSTRAINT fk_master_profile$master_profile_id$id 
                    FOREIGN KEY (master_profile_id) REFERENCES master_profile(id), 
                CONSTRAINT fk_salon_profile$salon_profile_id$id 
                    FOREIGN KEY (salon_profile_id) REFERENCES salon_profile(id)         
            );
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE orders;
        ');
    }
}
