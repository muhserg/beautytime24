<?php

class m190329_122208_delete_salon_inn extends CDbMigration
{
    // Use safeUp/safeDown to do migration with transaction
    public function safeUp()
    {
        $this->execute('
           ALTER TABLE salon_profile DROP KEY idx_ui_salon_profile$inn;
           ALTER TABLE salon_profile MODIFY inn NVARCHAR(12) NULL;
           ALTER TABLE salon_profile ADD COLUMN name_address_hash CHAR(32) NOT NULL;
           CREATE UNIQUE INDEX idx_ui_salon_profile$name_address_hash ON salon_profile(name_address_hash);
        ');
    }

    public function safeDown()
    {
        $this->execute('
            CREATE UNIQUE INDEX idx_ui_salon_profile$inn ON salon_profile(inn);
            ALTER TABLE salon_profile DROP COLUMN name_address_hash;
            ALTER TABLE salon_profile MODIFY inn NVARCHAR(12) NOT NULL;
            ALTER TABLE salon_profile DROP KEY idx_ui_salon_profile$name_address_hash;
        ');
    }
}
