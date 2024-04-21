<?php

use yii\db\Migration;

/**
 * Class m240421_035028_chat_record_content_table
 */
class m240421_035028_chat_record_content_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('chat_record_content', [
            'id' => $this->primaryKey(),
            'chatRecordId' => $this->integer()->comment('对应的对话记录id'),
            'content' => $this->text(),
            'version' => $this->integer()->defaultValue(0),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->createIndex('chatRecordId_unique_idx', 'chat_record_content', 'chatRecordId', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('chat_record_content');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240421_035028_chat_record_content_table cannot be reverted.\n";

        return false;
    }
    */
}
