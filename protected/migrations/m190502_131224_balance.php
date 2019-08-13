<?php

class m190502_131224_balance extends CDbMigration
{
    // Use safeUp/safeDown to do migration with transaction
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE user ADD COLUMN balance NUMERIC(10,2) NOT NULL DEFAULT 0;

            CREATE TABLE pay_operation_type (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name NVARCHAR(100) NOT NULL,
                rus_name NVARCHAR(100) NOT NULL
            );

            CREATE TABLE pay_operation (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED REFERENCES user(id),
                sum NUMERIC(10,2) NOT NULL DEFAULT 0,
                type_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                CONSTRAINT fk_pay_operation$user_id__user$id 
                    FOREIGN KEY (user_id) REFERENCES user(id),
                CONSTRAINT fk_pay_operation$type_id__pay_operation_type$id  
                    FOREIGN KEY (type_id) REFERENCES pay_operation_type(id)      
            );
            CREATE INDEX idx_in_pay_operation$user_id
                ON pay_operation(user_id);
            CREATE INDEX idx_in_pay_operation$type_id
                ON pay_operation(type_id);
                
            INSERT INTO pay_operation_type(name, rus_name) VALUES (\'add_payment\', \'пополнение баланса\');    
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE user DROP COLUMN balance;
            DROP TABLE pay_operation_type;
            DROP TABLE pay_operation;
        ');
    }
}
