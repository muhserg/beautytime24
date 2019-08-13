<?php

class m190226_093645_latitude_longitude extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE master_profile ADD latitude DOUBLE NULL;
            ALTER TABLE client_profile ADD latitude DOUBLE NULL;
            ALTER TABLE salon_profile ADD latitude DOUBLE NULL;

            ALTER TABLE master_profile ADD longitude DOUBLE NULL;
            ALTER TABLE client_profile ADD longitude DOUBLE NULL;
            ALTER TABLE salon_profile ADD longitude DOUBLE NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE master_profile DROP COLUMN latitude;
            ALTER TABLE client_profile DROP COLUMN latitude;
            ALTER TABLE salon_profile DROP COLUMN latitude;

            ALTER TABLE master_profile DROP COLUMN longitude;
            ALTER TABLE client_profile DROP COLUMN longitude;
            ALTER TABLE salon_profile DROP COLUMN longitude;
        ');
    }
}