<?php

class m190503_184500_sms_order_pay extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE user ADD COLUMN sms_pay_flag BOOLEAN NOT NULL DEFAULT false;
            
            INSERT INTO pay_operation_type(name, rus_name) VALUES (\'sms_pay\', \'оплата абонентской платы за смс оповещение\');    
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE user DROP COLUMN sms_pay_flag;
            DELETE FROM pay_operation_type WHERE  name = \'sms_pay\';
        ');
    }
}
