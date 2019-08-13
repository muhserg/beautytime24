<?php

class m190223_132709_buf_order extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE orders ADD address NVARCHAR(300) NULL;
            ALTER TABLE orders ADD address_coord NVARCHAR(300) NULL;
            ALTER TABLE orders ADD description NVARCHAR(300) NULL;
            ALTER TABLE orders ADD plan_price NUMERIC(10,2) NULL;
            ALTER TABLE orders ADD place NVARCHAR(50) NULL;

            CREATE TABLE buf_order (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                receipt_date DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                main_service_id INT UNSIGNED NOT NULL REFERENCES service(id),
                owner_session_php_id char(26) NOT NULL,
                master_profile_id INT UNSIGNED REFERENCES master_profile(id),
                salon_profile_id INT UNSIGNED REFERENCES salon_profile(id),
                plan_price NUMERIC(10,2) NULL,
                total NUMERIC(10,2) NOT NULL,
                plan_duration INT NULL,
                fact_duration INT NULL,
                address NVARCHAR(300) NULL,
                address_coord NVARCHAR(60) NULL,
                description NVARCHAR(2000) NULL,
                place NVARCHAR(50) NULL,
                rating INT NULL,
                CONSTRAINT fk_buf_service$main_service_id$id 
                    FOREIGN KEY (main_service_id) REFERENCES service(id),
                CONSTRAINT fk_buf_master_profile$master_profile_id$id 
                    FOREIGN KEY (master_profile_id) REFERENCES master_profile(id), 
                CONSTRAINT fk_buf_salon_profile$salon_profile_id$id 
                    FOREIGN KEY (salon_profile_id) REFERENCES salon_profile(id)         
            );
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE orders DROP COLUMN address;
            ALTER TABLE orders DROP COLUMN address_coord;
            ALTER TABLE orders DROP COLUMN description;
            ALTER TABLE orders DROP COLUMN plan_price;
            ALTER TABLE orders DROP COLUMN place;
            
            DROP TABLE buf_order;
        ');
    }
}