<?php

use yii\db\Migration;

/**
 * Class m240421_031000_chat_record_table
 */
class m240421_031000_chat_record_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('chat_record', [
            'id' => $this->primaryKey(),
            'chatSessionId' => $this->integer()->comment('会话总id'),
            'userId' => $this->integer()->comment('用户id'),
            'storyId' => $this->integer()->comment('对应的故事id'),
            'roleId' => $this->integer()->comment('角色的系统内部编号'),
            'createAt' => $this->integer(),
            'pairChatRecordId' => $this->integer()->comment('role对应的model输出记录id，model对应的role输入记录id'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->createIndex('chatSessionId_storyId_idx', 'chat_record', ['chatSessionId', 'storyId']);
        $this->createIndex('storyId_idx', 'chat_record', 'storyId');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%chat_record}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240421_031000_chat_record_table cannot be reverted.\n";

        return false;
    }
    */
}
