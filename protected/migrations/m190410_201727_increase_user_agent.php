<?php

class m190410_201727_increase_user_agent extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE hit MODIFY user_agent NVARCHAR(2000) NULL;
            ALTER TABLE session MODIFY user_agent NVARCHAR(2000) NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
           ALTER TABLE session MODIFY user_agent NVARCHAR(256) NULL;
           ALTER TABLE hit MODIFY user_agent NVARCHAR(256) NULL;
           
        ');
    }
}
