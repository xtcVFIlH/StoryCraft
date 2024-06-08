<?php

use yii\db\Migration;

/**
 * Class m240604_015452_alter_background_info_in_story_table
 */
class m240604_015452_alter_background_info_in_story_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('story', 'backgroundInfo', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('story', 'backgroundInfo', $this->string(1000));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240604_015452_alter_background_info_in_story_table cannot be reverted.\n";

        return false;
    }
    */
}
