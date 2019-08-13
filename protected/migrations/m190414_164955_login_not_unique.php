<?php

class m190414_164955_login_not_unique extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE user DROP KEY idx_user$login;
            CREATE INDEX idx_in_user$login ON user(login);
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE user DROP KEY idx_in_user$login;
            CREATE UNIQUE INDEX idx_user$login ON user(login);
        ');
    }
}
