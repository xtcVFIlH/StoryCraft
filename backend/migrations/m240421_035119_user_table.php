<?php

use yii\db\Migration;

/**
 * Class m240421_035119_user_table
 */
class m240421_035119_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('user', [
            'id' => $this->primaryKey(),
            'accessToken' => $this->string(10)->comment('用户的校验token'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->createIndex('accessToken_unique_idx', 'user', 'accessToken', true);

        // 默认用户
        $this->insert('user', [
            'accessToken' => '8nD5k1hopW',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('user');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240421_035119_user_table cannot be reverted.\n";

        return false;
    }
    */
}