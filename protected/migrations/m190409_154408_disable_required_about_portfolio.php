<?php

class m190409_154408_disable_required_about_portfolio extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE master_portfolio MODIFY about NVARCHAR(2000) NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
           ALTER TABLE master_portfolio MODIFY about NVARCHAR(2000) NOT NULL;
        ');
    }
}
