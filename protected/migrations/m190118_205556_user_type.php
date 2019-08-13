<?php

class m190118_205556_user_type extends CDbMigration
{
    public function safeUp()
    {
        $this->execute("
            INSERT INTO user_type(name, rus_name) VALUES ('client', 'клиент');
            INSERT INTO user_type(name, rus_name) VALUES ('master', 'мастер');
            INSERT INTO user_type(name, rus_name) VALUES ('salon', 'салон');
            INSERT INTO user_type(name, rus_name) VALUES ('promoter', 'промоутер');
            INSERT INTO user_type(name, rus_name) VALUES ('provider', 'поставщик');
            INSERT INTO user_type(name, rus_name) VALUES ('admin', 'администратор');
        ");
    }

    public function safeDown()
    {
        $this->execute('
           DELETE FROM user_type;
        ');
    }
}
