<?php

use yii\db\Migration;

/**
 * Handles the dropping of table `{{%frontend_proxy_temp}}`.
 */
class m240726_033135_drop_frontend_proxy_temp_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropTable('frontend_proxy_temp');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->createTable('frontend_proxy_temp', [
            'id' => $this->primaryKey(),
            'chatSessionId' => $this->integer()->comment('会话总id'),
            'tempId' => $this->string(10)->comment('临时id'),
            'createAt' => $this->integer(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->createIndex('tempId_idx', 'frontend_proxy_temp', 'tempId');
    }
}
