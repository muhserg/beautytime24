<?php

class m190402_065358_review_indexes extends CDbMigration
{
    public function safeUp()
    {
        $this->execute('
            CREATE INDEX idx_in_review$user_id
                ON review(user_id);
            CREATE INDEX idx_in_review$order_id
                ON review(order_id);
        ');
    }

    public function safeDown()
    {
        $this->execute('
            ALTER TABLE review DROP KEY idx_in_review$user_id;
            ALTER TABLE review DROP KEY idx_in_review$order_id;
        ');
    }
}
