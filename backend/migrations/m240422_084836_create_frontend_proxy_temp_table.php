<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%frontend_proxy_temp}}`.
 */
class m240422_084836_create_frontend_proxy_temp_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('frontend_proxy_temp', [
            'id' => $this->primaryKey(),
            'chatSessionId' => $this->integer()->comment('会话总id'),
            'tempId' => $this->string(10)->comment('临时id'),
            'createAt' => $this->integer(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->createIndex('tempId_idx', 'frontend_proxy_temp', 'tempId');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('frontend_proxy_temp');
    }
}
