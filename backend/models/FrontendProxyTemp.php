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

    /**
     * 生成一个新的临时记录，并返回相关信息
     * @param Int $chatSessionId 会话ID
     * @param String $url 需要前端代理请求的URL
     * @param String $json 需要前端代理请求的JSON数据字符串
     * @param Boolean $isGeneratedContentJson 该请求中生成的数据是否为JSON格式
     * @param Array $query 需要前端代理请求的查询参数，默认为空数组
     * @throws \Exception 保存失败时抛出异常
     * @return Array
     */
    public static function saveNewTemp(
        $chatSessionId, $url, $json, $isGeneratedContentJson, $query = []
    )
    {
        $record = new static;
        $record->chatSessionId = $chatSessionId;
        $record->tempId = substr(md5(uniqid()), 0, 10);
        $record->isJson = $isGeneratedContentJson ? 1 : 0;
        if (!$record->save()) {
            throw new \Exception('Failed to save new frontend proxy record');
        }
        return [
            'url' => $url,
            'json' => $json,
            'query' => $query,
            'tempId' => $record->tempId,
        ];
    }

}