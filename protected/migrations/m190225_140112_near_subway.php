<?php

class m190225_140112_near_subway extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE master_profile ADD near_subway NVARCHAR(100) NULL;
            ALTER TABLE client_profile ADD near_subway NVARCHAR(100) NULL;
            ALTER TABLE salon_profile ADD near_subway NVARCHAR(100) NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE master_profile DROP COLUMN near_subway;
            ALTER TABLE client_profile DROP COLUMN near_subway;
            ALTER TABLE salon_profile DROP COLUMN near_subway;
        ');
    }
}
