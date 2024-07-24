<?php

namespace app\components;

use Yii;
use \Exception;
use \app\dto\story\StoryContent as StoryContentClientDTO;

class Story
{

    /** @var story\StoryPromptHandler */
    public $storyPromptHandler;

    function __construct()
    {
        $this->storyPromptHandler = Yii::$app->__storyPromptHandler;
    }

    /**
     * @param \app\dto\story\StoryInfo $storyInfoDto
     * @return Int 故事ID
     */
    public function updateStory($storyInfoDto, $userId, $storyId = null)
    {
        if ($storyId) 
        {
            $story = \app\models\Story::findOne($storyId);
            if (!$story) {
                throw new Exception('未找到故事[ ' . $storyId . ' ]');
            }
        }
        else
        {
            $story = new \app\models\Story();
        }

        $data = $storyInfoDto->toArray();
        $characters = $data['characterInfos'];

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $story->title = $data['title'];
            $story->backgroundInfo = $data['backgroundInfo'];
            $story->userId = $userId;
            if (!$story->save()) {
                // 获取详细错误信息
                $errors = $story->getErrors();
                $error = array_shift($errors);
                throw new Exception(array_shift($error));
            }
            \app\models\story\Character::deleteAll(['storyId' => $story->id]); 
            foreach ($characters as $character) {
                $characterModel = new \app\models\story\Character();
                $characterModel->storyId = $story->id;
                $characterModel->name = $character['name'];
                $characterModel->feature = $character['feature'];
                if (isset($character['avatar'])) {
                    $characterModel->avatar = $character['avatar'];
                }
                if (!$characterModel->save()) {
                    // 获取详细错误信息
                    $errors = $characterModel->getErrors();
                    $error = array_shift($errors);
                    throw new Exception(array_shift($error));
                }
            }
            $transaction->commit();
            return $story->id;
        }
        catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 生成新故事
     * @param Int $userId 用户ID
     * @param String $userPrompt 用户输入的提示词
     * @param Int $storyId 故事ID
     * @param Int|Null $chatSessionId 会话ID 为null时表示新会话
     * @return Array|Boolean 获取失败时返回false，否则返回数据数组，或在使用前端代理时返回请求信息
     */
    public function getNewStory($userId, $userPrompt, $storyId, $chatSessionId)
    {
        $story = \app\models\Story::findOne($storyId);
        if (!$story) {
            throw new Exception('未找到故事');
        }
        if ($story->userId != $userId) {
            throw new Exception('故事不属于该用户');
        }

        if (!$chatSessionId) {
            $chatSession = \app\models\chat\ChatSession::saveNewOne(
                $userId, $storyId, $story->title
            );
        }
        else {
            $chatSession = \app\models\chat\ChatSession::findOne($chatSessionId);
        }
        if (!$chatSession) {
            throw new Exception('获取会话失败');
        }
        if ($chatSession->userId != $userId) {
            throw new Exception('会话不属于该用户');
        }
        if ($chatSession->storyId != $storyId) {
            throw new Exception('会话不属于该故事');
        }
        $chatSessionId = $chatSession->id;

        $userPrompt = trim($userPrompt);
        if (!$userPrompt) {
            throw new Exception('用户输入的提示词不能为空');
        }

        $prompts = $this->storyPromptHandler->getPrompts($story, $chatSessionId, $userPrompt, $chatSession->customInstructions);
        $systemInstruction = $prompts['system'];
        $prompts = $prompts['user'];

        if (yii::$app->params['usingFrontendProxy']) {
            // 使用前端代理，直接返回需要代理的请求信息
            return [
                'frontendProxy' => \app\models\FrontendProxyTemp::saveNewTemp(
                    $chatSessionId,
                    yii::$app->gemini->getGenerateContentUrl('gemini-1.5-pro'),
                    yii::$app->gemini->getGenerateContenctRequestBody(
                        $prompts,
                        $systemInstruction,
                        1.0,
                        true
                    ),
                    true
                ),
            ];
        }

        $generateContents = yii::$app->gemini->generateContentInMultiTurnConversations(
            'gemini-1.5-pro', 
            $prompts, 
            $systemInstruction, 
            true
        );
        return $this->saveGeneratedContent(
            $storyId, $userId,
            $chatSession,
            $generateContents,
            $userPrompt
        );
    }

    /**
     * 生成故事后，将新生成的内容进行保存
     * @param Int $storyId 故事ID
     * @param Int $userId 用户ID
     * @param \app\models\chat\ChatSession $chatSession 会话对象
     * @param Array $generateContents 生成的内容
     * @param String $userPrompt 用户输入的提示词
     * @throws \Throwable
     * @return Array{
     *   chatSessionInfo: Array{
     *     id: Int,
     *     title: String
     *   },
     *   storyContents: \app\dto\story\StoryContent[]
     * } 返回会话信息和新生成的故事内容（用户输入和模型输出）
     */    
    public function saveGeneratedContent(
        $storyId, $userId,
        $chatSession,
        $generateContents,
        $userPrompt
    )
    {
        $storySegment = new Story\StorySegment($generateContents);

        // 保存用户输入和模型输出
        $transaction = Yii::$app->db->beginTransaction();
        try {
            [$userChatRecordId, $modelChatRecordId] = \app\models\chat\ChatRecord::saveNewPair(
                $storyId, $chatSession->id, $userId,
                $userPrompt, $storySegment->getContentInJson()
            );
            $transaction->commit();
        }
        catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return [
            'chatSessionInfo' => [
                'id' => $chatSession->id,
                'title' => $chatSession->title,
            ],
            'storyContents' => [
                StoryContentClientDTO::newUserContent($userChatRecordId, $userPrompt),
                StoryContentClientDTO::newModelContent($modelChatRecordId, $storySegment),
            ],
        ];
    }

    /**
     * 获取故事的历史内容
     * @return StoryContentClientDTO[]
     */
    public function getAllStoryContents($userId, $storyId, $chatSessionId)
    {
        $chatRecords = \app\models\chat\ChatRecord::find()
            ->where([
                'chatSessionId' => $chatSessionId,
                'storyId' => $storyId,
            ])
            ->with('contentRecord')
            ->orderBy('id ASC')
            ->all();
        $chatRecordsArray = [];
        foreach ($chatRecords as $record) {
            if (!$record->contentRecord) {
                throw new Exception('获取历史故事内容失败');
            }
            if ($record->userId != $userId) {
                throw new Exception('用户不匹配');
            }
            if ($record->isUserChat()) {
                $chatRecordsArray []= StoryContentClientDTO::newUserContent($record->id, $record->contentRecord->content);
            }
            else {
                $storySegment = new story\StorySegment($record->contentRecord->content);
                $chatRecordsArray []= StoryContentClientDTO::newModelContent($record->id, $storySegment);
            }
        }
        return $chatRecordsArray;
    }

    /**
     * 删除一对用户输入、模型输出的记录
     * @param Int $chatRecordId 聊天记录ID
     * @param Int $userId 用户ID
     * @return Array{0: Int, 1: Int}
     */
    public function deleteChatRecordPair($chatRecordId, $userId)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $deletedChatRecordIds = \app\models\chat\ChatRecord::deletePair($chatRecordId, $userId);
            $transaction->commit();
            
            return $deletedChatRecordIds;
        }
        catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 删除模型输出的故事片段中某个情节的内容
     * @param Int $chatRecordId 聊天记录ID
     * @param Int $userId 用户ID
     * @param Int $itemInx 情节索引
     * @return \app\components\story\StorySegment 删除后的故事片段
     */
    public function deleteModelContent($chatRecordId, $userId, $itemInx)
    {
        $chatRecord = \app\models\chat\ChatRecord::find()
            ->where(['id' => $chatRecordId, 'userId' => $userId])
            ->with('contentRecord')
            ->one();
        if (!$chatRecord || !$chatRecord->contentRecord) {
            throw new Exception('未找到记录');
        }
        if (!$chatRecord->isModelChat()) {
            throw new Exception('非模型输出');
        }
        $storySegment = new story\StorySegment($chatRecord->contentRecord->content);
        $storySegment->deleteContent($itemInx);
        $chatRecord->contentRecord->content = $storySegment->getContentInJson();
        if (!$chatRecord->contentRecord->save()) {
            throw new Exception('保存失败');
        }
        return $storySegment;
    }

    /**
     * 更新模型输出的故事片段中某个情节的内容
     * @param Int $chatRecordId 聊天记录ID
     * @param Int $userId 用户ID
     * @param Int $itemInx 情节索引
     * @param String $newItemContent 新情节内容
     * @return \app\components\story\StorySegment 更新后的故事片段
     */
    public function editModelContent($chatRecordId, $userId, $itemInx, $newItemContent)
    {
        $newItemContent = trim($newItemContent);
        $newItemContent = mb_ereg_replace("\r\n|\n|\r", '', $newItemContent);
        
        $chatRecord = \app\models\chat\ChatRecord::find()
            ->where(['id' => $chatRecordId, 'userId' => $userId])
            ->with('contentRecord')
            ->one();
        if (!$chatRecord || !$chatRecord->contentRecord) {
            throw new Exception('未找到记录');
        }
        if (!$chatRecord->isModelChat()) {
            throw new Exception('非模型输出');
        }
        $storySegment = new story\StorySegment($chatRecord->contentRecord->content);
        $storySegment->editContent($itemInx, $newItemContent);
        $chatRecord->contentRecord->content = $storySegment->getContentInJson();
        if (!$chatRecord->contentRecord->save()) {
            throw new Exception('保存失败');
        }
        return $storySegment;
    }

    /**
     * 获取用户所有的故事ID和标题
     * @param Int $userId 用户ID
     * @return Array{
     *   id: Int,
     *   title: String
     * }[]
     */
    public function getStoriesWithIdAndTitle($userId) {
        $stories = \app\models\Story::find()
            ->select(['id', 'title'])
            ->where(['userId' => $userId])
            ->orderBy('id DESC')
            ->all();
        return array_map(function($record) {
            return [
                'id' => $record->id,
                'title' => $record->title,
            ];
        }, $stories);
    }

    /**
     * 删除某个会话的所有内容
     * @param Int $storyId 故事ID
     * @param Int $chatSessionId 会话ID
     * @param Int $userId 用户ID
     * @return Void
     */
    public function deleteChatSession($storyId, $chatSessionId, $userId)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            \app\models\chat\ChatSession::deleteOne($chatSessionId, $storyId, $userId);
            $transaction->commit();
        }
        catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

}