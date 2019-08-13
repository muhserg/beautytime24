<?php

class m190325_072929_profile_place extends CDbMigration
{
    // Use safeUp/safeDown to do migration with transaction
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE client_profile ADD place NVARCHAR(50) NULL;
            ALTER TABLE master_profile ADD place NVARCHAR(50) NULL;
            ALTER TABLE salon_profile ADD place NVARCHAR(50) NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE client_profile DROP COLUMN place;
            ALTER TABLE master_profile DROP COLUMN place;
            ALTER TABLE salon_profile DROP COLUMN place;
        ');
    }
}
