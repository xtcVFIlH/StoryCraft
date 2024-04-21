<?php

use yii\db\Migration;

/**
 * Class m240421_035042_chat_session_table
 */
class m240421_035042_chat_session_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('chat_session', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer(),
            'storyId' => $this->integer(),
            'title' => $this->string(255),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('chat_session');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240421_035042_chat_session_table cannot be reverted.\n";

        return false;
    }
    */
}
