<?php

use yii\db\Migration;

/**
 * Class m240421_035108_story_character_table
 */
class m240421_035108_story_character_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('story_character', [
            'id' => $this->primaryKey(),
            'storyId' => $this->integer()->comment('对应的故事id'),
            'name' => $this->string(20)->comment('角色的姓名'),
            'feature' => $this->string(400)->comment('角色特征'),
            'avatar' => $this->string(255)->comment('头像url'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->createIndex('storyId_idx', 'story_character', 'storyId');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('story_character');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240421_035108_story_character_table cannot be reverted.\n";

        return false;
    }
    */
}
