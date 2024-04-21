<?php

namespace app\models\chat;

use yii;
use yii\db\ActiveRecord;

// 记录聊天记录的内容
class ChatRecordContent extends ActiveRecord {

    public static function tableName()
    {
        return 'chat_record_content';
    }

    public function optimisticLock()
    {
        return 'version';
    }

}