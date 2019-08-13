<?php

class m190405_204313_add_phone extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE client_profile ADD phone NVARCHAR(15) NULL;
            ALTER TABLE master_profile ADD phone NVARCHAR(15) NULL;
            ALTER TABLE salon_profile ADD phone NVARCHAR(15) NULL;
            
            ALTER TABLE vacancy ADD phone NVARCHAR(15) NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE client_profile DROP COLUMN phone;
            ALTER TABLE master_profile DROP COLUMN phone;
            ALTER TABLE salon_profile DROP COLUMN phone;
            
            ALTER TABLE vacancy DROP COLUMN phone;
        ');
    }
}
