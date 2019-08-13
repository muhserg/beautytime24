<?php

class m190403_093402_order_near_subway extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE buf_order ADD COLUMN near_subway NVARCHAR(100) NULL;
            ALTER TABLE orders ADD COLUMN near_subway NVARCHAR(100) NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE buf_order DROP COLUMN near_subway;
            ALTER TABLE orders DROP COLUMN near_subway;
        ');
    }
}
