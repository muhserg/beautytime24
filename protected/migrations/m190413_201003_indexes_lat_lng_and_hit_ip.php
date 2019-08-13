<?php

class m190413_201003_indexes_lat_lng_and_hit_ip extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE INDEX idx_in_orders$latitude$longitude ON orders(latitude, longitude);
            CREATE INDEX idx_in_orders$near_subway ON orders(near_subway);

            CREATE INDEX idx_in_client_profile$latitude$longitude ON client_profile(latitude, longitude);
            CREATE INDEX idx_in_client_profile$near_subway ON client_profile(near_subway);

            CREATE INDEX idx_in_master_profile$latitude$longitude ON master_profile(latitude, longitude);
            CREATE INDEX idx_in_master_profile$near_subway ON master_profile(near_subway);
            
            CREATE INDEX idx_in_salon_profile$latitude$longitude ON salon_profile(latitude, longitude);
            CREATE INDEX idx_in_salon_profile$near_subway ON salon_profile(near_subway);
            
            ALTER TABLE hit ADD COLUMN user_ip NVARCHAR(40) NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE orders DROP KEY idx_in_orders$latitude$longitude;
            ALTER TABLE orders DROP KEY idx_in_orders$near_subway;

            ALTER TABLE client_profile DROP KEY idx_in_client_profile$latitude$longitude;
            ALTER TABLE client_profile DROP KEY idx_in_client_profile$near_subway;

            ALTER TABLE master_profile DROP KEY idx_in_master_profile$latitude$longitude;
            ALTER TABLE master_profile DROP KEY idx_in_master_profile$near_subway;
            
            ALTER TABLE salon_profile DROP KEY idx_in_salon_profile$latitude$longitude;
            ALTER TABLE salon_profile DROP KEY idx_in_salon_profile$near_subway;
            ALTER TABLE hit DROP COLUMN user_ip;
        ');
    }
}
