<?php

use yii\db\Migration;

/**
 * Class m240604_024910_alter_name_and_feature_in_story_character_table
 */
class m240604_024910_alter_name_and_feature_in_story_character_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('story_character', 'name', $this->string(255));
        $this->alterColumn('story_character', 'feature', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('story_character', 'name', $this->string(20));
        $this->alterColumn('story_character', 'feature', $this->string(400));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240604_024910_alter_name_and_feature_in_story_character_table cannot be reverted.\n";

        return false;
    }
    */
}
