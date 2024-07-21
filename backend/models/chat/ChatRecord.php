<?php

namespace app\models\chat;

use yii;
use yii\db\ActiveRecord;
use \Exception;

// 记录单条聊天记录
class ChatRecord extends ActiveRecord {

    public static function tableName()
    {
        return 'chat_record';
    }

    public function getContentRecord()
    {
        return $this->hasOne(ChatRecordContent::className(), ['chatRecordId' => 'id']);
    }

    protected static $ROLE_USER = 1;
    protected static $ROLE_MODEL = 2;
    public function isUserChat()
    {
        return $this->roleId == static::$ROLE_USER;
    }
    public function isModelChat()
    {
        return $this->roleId == static::$ROLE_MODEL;
    }

    /**
     * @return \app\models\chat\ChatRecord 保存的记录实例
     */
    static protected function saveNewOne(
        $storyId, $chatSessionId, $userId,
        $isUserChat,
        $content
    ) 
    {
        $record = new static;
        $record->chatSessionId = $chatSessionId;
        $record->storyId = $storyId;
        $record->userId = $userId;
        $record->roleId = $isUserChat ? static::$ROLE_USER : static::$ROLE_MODEL;
        $record->createAt = time();
        if (!$record->save()) {
            throw new Exception('Failed to save chat record');
        }
        $contentRecord = new ChatRecordContent;
        $contentRecord->chatRecordId = $record->id;
        $contentRecord->content = $content;
        if (!$contentRecord->save()) {
            throw new Exception('Failed to save chat record content');
        }
        return $record;
    }

    /**
     * 新建一对聊天记录（用户和模型），需要在事务中调用
     * @return Array{0: Int, 1: Int} 新建的记录ID，包含用户和模型的记录ID
     */
    static public function saveNewPair(
        $storyId, $chatSessionId, $userId,
        $userContent, $modelContent
    )
    {
        $userRecord = static::saveNewOne($storyId, $chatSessionId, $userId, true, $userContent);
        $modelRecord = static::saveNewOne($storyId, $chatSessionId, $userId, false, $modelContent);
        $userRecord->pairChatRecordId = $modelRecord->id;
        $modelRecord->pairChatRecordId = $userRecord->id;
        if (!$userRecord->save() || !$modelRecord->save()) {
            throw new Exception('Failed to save chat record pair');
        }
        return [
            $userRecord->id,
            $modelRecord->id,
        ];
    }

    /**
     * 删除一对聊天记录，需要在事务中调用
     * @return Array{0: Int, 1: Int} 删除的记录ID，包含用户和模型的记录ID
     */
    static public function deletePair(
        $chatRecordId, $userId   
    )
    {
        $record = static::findOne([
            'id' => $chatRecordId,
            'userId' => $userId,
        ]);
        if (!$record || !$record->pairChatRecordId) {
            throw new Exception('Chat record not found');
        }
        if (static::deleteAll([
            'and',
            [
                'in', 'id', [$record->id, $record->pairChatRecordId],
            ],
        ]) != 2) {
            throw new Exception('Failed to delete chat record pair');
        }
        if (ChatRecordContent::deleteAll([
            'and',
            [
                'in', 'chatRecordId', [$record->id, $record->pairChatRecordId],
            ],
        ]) != 2) {
            throw new Exception('Failed to delete chat record content pair');
        }
        return [
            $record->id,
            $record->pairChatRecordId,
        ];
    }

}