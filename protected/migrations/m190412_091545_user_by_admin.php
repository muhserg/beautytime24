<?php

class m190412_091545_user_by_admin extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE `user` ADD COLUMN admin_flag BOOLEAN DEFAULT false NOT NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
           ALTER TABLE `user` DROP COLUMN admin_flag;
        ');
    }
}
