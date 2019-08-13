<?php

class m190508_113654_add_order_photo extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE orders ADD photo_file_name NVARCHAR(40) NULL;
            ALTER TABLE buf_order ADD photo_file_name NVARCHAR(40) NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE orders DROP COLUMN photo_file_name;
            ALTER TABLE buf_order DROP COLUMN photo_file_name;
        ');
    }
}
