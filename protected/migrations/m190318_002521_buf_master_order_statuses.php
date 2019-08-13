<?php

class m190318_002521_buf_master_order_statuses extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE order_status (
                id INT UNSIGNED PRIMARY KEY,
                name NVARCHAR(100) NOT NULL
            );
            
            INSERT INTO order_status VALUES(1, \'Создан\');
            INSERT INTO order_status VALUES(2, \'Отправлен на согласование\');
            INSERT INTO order_status VALUES(3, \'Согласован\');
            INSERT INTO order_status VALUES(4, \'Сделан\');
            INSERT INTO order_status VALUES(5, \'Оценен\');
            INSERT INTO order_status VALUES(6, \'Просрочен\');
            INSERT INTO order_status VALUES(7, \'Удален\');
            
            CREATE TABLE buf_master_order (
                profile_id INT UNSIGNED REFERENCES master_profile(id),
                order_id INT UNSIGNED REFERENCES orders(id),
                client_agree BOOLEAN,
                master_agree BOOLEAN,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                CONSTRAINT fk_master_profile_order$profile_id__profile$id 
                    FOREIGN KEY (profile_id) REFERENCES master_profile(id),
                CONSTRAINT fk_master_profile_order$order_id__orders$id 
                    FOREIGN KEY (order_id) REFERENCES orders(id)    
            );
            
            CREATE TABLE buf_salon_order (
                profile_id INT UNSIGNED REFERENCES salon_profile(id),
                order_id INT UNSIGNED REFERENCES orders(id),
                client_agree BOOLEAN,
                salon_agree BOOLEAN,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                CONSTRAINT fk_salon_profile_order$profile_id__profile$id 
                    FOREIGN KEY (profile_id) REFERENCES salon_profile(id),
                CONSTRAINT fk_salon_profile_order$order_id__orders$id 
                    FOREIGN KEY (order_id) REFERENCES orders(id)    
            );
        ');
    }

    public function safeDown()
    {
        $this->execute('
            DROP TABLE order_status;
            DROP TABLE buf_master_order;
            DROP TABLE buf_salon_order;
        ');
    }
}