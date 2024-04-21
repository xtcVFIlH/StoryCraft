<?php

use yii\db\Migration;

/**
 * Class m240421_035048_story_table
 */
class m240421_035048_story_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('story', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->comment('用户id'),
            'title' => $this->string(255)->comment('故事名称'),
            'backgroundInfo' => $this->string(1000),
            'createAt' => $this->integer(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('story');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240421_035048_story_table cannot be reverted.\n";

        return false;
    }
    */
}
