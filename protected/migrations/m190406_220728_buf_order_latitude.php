<?php

class m190406_220728_buf_order_latitude extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE buf_order ADD latitude DOUBLE NULL;
            ALTER TABLE buf_order ADD longitude DOUBLE NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE buf_order DROP COLUMN latitude;
            ALTER TABLE buf_order DROP COLUMN longitude;
        ');
    }
}
