<?php

class m190410_083925_send_order_sms extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE orders ADD COLUMN send_sms_order_create_flag BOOLEAN DEFAULT false NOT NULL;

            CREATE TABLE sms (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                phone NVARCHAR(30) NOT NULL,
                type NVARCHAR(30) NOT NULL,
                message NVARCHAR(500) NOT NULL,
                response NVARCHAR(2000) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            );

            ALTER TABLE client_profile ADD COLUMN sms_confirm BOOLEAN DEFAULT true NOT NULL;
            ALTER TABLE master_profile ADD COLUMN sms_confirm BOOLEAN DEFAULT true NOT NULL;
            ALTER TABLE salon_profile ADD COLUMN sms_confirm BOOLEAN DEFAULT true NOT NULL;
        ');
    }

    public function safeDown()
    {
        $this->execute('
           ALTER TABLE orders DROP COLUMN send_sms_order_create_flag;

           ALTER TABLE client_profile DROP COLUMN sms_confirm;
           ALTER TABLE master_profile DROP COLUMN sms_confirm;
           ALTER TABLE salon_profile DROP COLUMN sms_confirm;

           DROP TABLE sms; 
        ');
    }
}
