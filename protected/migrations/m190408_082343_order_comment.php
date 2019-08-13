<?php

class m190408_082343_order_comment extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE message ADD order_id INT UNSIGNED NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE message DROP COLUMN order_id;
        ');
    }
}
