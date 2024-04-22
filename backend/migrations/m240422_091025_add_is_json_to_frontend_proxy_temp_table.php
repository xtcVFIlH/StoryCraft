<?php

use yii\db\Migration;

/**
 * Class m240422_091025_add_is_json_to_frontend_proxy_temp_table
 */
class m240422_091025_add_is_json_to_frontend_proxy_temp_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('frontend_proxy_temp', 'isJson', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('frontend_proxy_temp', 'isJson');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240422_091025_add_is_json_to_frontend_proxy_temp_table cannot be reverted.\n";

        return false;
    }
    */
}
