<?php

use yii\db\Migration;

/**
 * Class m240530_074336_add_custom_instructions_to_chat_session_table
 */
class m240530_074336_add_custom_instructions_to_chat_session_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('chat_session', 'customInstructions', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('chat_session', 'customInstructions');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240530_074336_add_custom_instructions_to_chat_session_table cannot be reverted.\n";

        return false;
    }
    */
}
