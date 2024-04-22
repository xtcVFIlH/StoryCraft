<?php

namespace app\models;

class FrontendProxyTemp extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'frontend_proxy_temp';
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->createAt = time();
            }
            return true;
        }
        return false;
    }

    public function getChatSession()
    {
        return $this->hasOne(chat\ChatSession::class, ['id' => 'chatSessionId']);
    }

}