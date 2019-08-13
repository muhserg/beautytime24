<?php

class m190404_154515_order_latitude_longitude extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE orders ADD latitude DOUBLE NULL;
            ALTER TABLE orders ADD longitude DOUBLE NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE orders DROP COLUMN latitude;
            ALTER TABLE orders DROP COLUMN longitude;
        ');
    }
}
