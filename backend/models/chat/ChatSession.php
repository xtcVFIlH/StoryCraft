<?php

namespace app\models\chat;

use yii;
use \Exception;

// 聊天会话
class ChatSession extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'chat_session';
    }

    public function getRecords()
    {
        return $this->hasMany(ChatRecord::className(), ['chatSessionId' => 'id']);
    }

    /**
     * @return \app\models\chat\ChatSession
     */
    static public function saveNewOne(
        $userId, $storyId, $storyTitle
    )
    {
        $chatSession = new ChatSession();
        $chatSession->userId = $userId;
        $chatSession->storyId = $storyId;
        $chatSession->title = $storyTitle . mt_rand(10000, 99999);
        if (!$chatSession->save()) {
            throw new Exception('Failed to save chat session');
        }
        return $chatSession;
    }

    /**
     * 需要在事务中调用
     * @return Void
     */
    static public function deleteOne(
        $chatSessionId, $storyId, $userId
    )
    {
        $chatSession = static::findOne([
            'id' => $chatSessionId,
            'storyId' => $storyId,
            'userId' => $userId,
        ]);
        if (!$chatSession) {
            throw new Exception('Chat session not found');
        }
        if (!$chatSession->delete()) {
            throw new Exception('Failed to delete chat session');
        }
        $chatRecords = ChatRecord::find()
            ->where([
                'chatSessionId' => $chatSessionId,
            ])
            ->with('contentRecord')
            ->asArray()
            ->all();
        $recordIds = array_map(function($record) {
            return $record['id'];
        }, $chatRecords);
        $recordContentIds = array_map(function($record) {
            return $record['contentRecord']['id'];
        }, $chatRecords);
        if (!empty($recordIds)) {
            if (ChatRecord::deleteAll([
                'in', 'id', $recordIds,
            ]) != count($recordIds)) {
                throw new Exception('Failed to delete chat records');
            }
        }
        if (!empty($recordContentIds)) {
            if (ChatRecordContent::deleteAll([
                'in', 'id', $recordContentIds,
            ]) != count($recordContentIds)) {
                throw new Exception('Failed to delete chat record contents');
            }
        }
    }
}
