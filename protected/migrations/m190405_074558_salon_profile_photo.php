<?php

class m190405_074558_salon_profile_photo extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE salon_profile ADD small_logo_file_name NVARCHAR(40) NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE salon_profile DROP COLUMN small_logo_file_name;
        ');
    }
}
