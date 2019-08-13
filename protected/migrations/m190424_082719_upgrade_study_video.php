<?php

class m190424_082719_upgrade_study_video extends CDbMigration
{
    // Use safeUp/safeDown to do migration with transaction
    public function safeUp()
    {
        $this->execute('
           ALTER TABLE study_video ADD COLUMN created_at DATETIME NOT NULL;
           ALTER TABLE study_video ADD COLUMN updated_at DATETIME NOT NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE study_video DROP COLUMN created_at;
            ALTER TABLE study_video DROP COLUMN updated_at;
        ');
    }
}
