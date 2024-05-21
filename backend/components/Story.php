<?php

namespace app\components;

use Yii;
use \Exception;

class Story
{

    /** @var story\PromptHandler */
    public $promptHandler;

    function __construct()
    {
        $this->promptHandler = Yii::$app->__storyPromptHandler;
    }

    /**
     * @return Int 故事ID
     */
    public function updateStory($data, $userId, $storyId = null)
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

        // 简单校验，具体校验由model完成
        if (!isset($data['title'])) {
            throw new Exception('故事标题不能为空');
        }
        if (!isset($data['backgroundInfo'])) {
            throw new Exception('故事背景不能为空');
        }
        if (!isset($data['characterInfos'])) {
            throw new Exception('故事角色不能为空');
        }
        if (!is_array($data['characterInfos'])) {
            throw new Exception('故事角色格式错误');
        }
        $characters = $data['characterInfos'];
        if (empty($characters)) {
            throw new Exception('故事角色不能为空');
        }
        foreach ($characters as $character) {
            if (!isset($character['name'])) {
                throw new Exception('角色名称不能为空');
            }
            if (!isset($character['feature'])) {
                throw new Exception('角色特征不能为空');
            }
        }

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
        if (mb_strlen($userPrompt) > 200) {
            throw new Exception('用户输入的提示词不能超过200个字符');
        }
        if (!$userPrompt) {
            throw new Exception('用户输入的提示词不能为空');
        }
        // 查看是否包含---、```、换行符
        if (preg_match('/```|---|\n/', $userPrompt)) {
            throw new Exception('用户提示词不符合格式要求');
        }

        $prompts = $this->promptHandler->getPrompts($story, $chatSessionId, $userPrompt);
        $systemInstruction = $prompts['system'];
        $prompts = $prompts['user'];

        if (yii::$app->LLM->usingFrontendProxy) {
            // 使用前端代理，直接返回需要代理的请求信息
            $frontendProxy = yii::$app->LLM->getRequestDataFrontendProxy(
                $prompts, $systemInstruction, true
            );
            $tempRecord = new \app\models\FrontendProxyTemp();
            $tempRecord->chatSessionId = $chatSessionId;
            $tempRecord->tempId = $frontendProxy['tempId'];
            $tempRecord->isJson = 1;
            if (!$tempRecord->save()) {
                throw new Exception('保存前端代理临时记录失败');
            }
            return [
                'frontendProxy' => $frontendProxy,
            ];
        }

        $generateContents = yii::$app->LLM->generateChatContent(
            $prompts, $systemInstruction, true
        );
        $generateContentJson = json_encode($generateContents, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->promptHandler->validateGeneratedContent($generateContentJson);

        // 保存用户输入和模型输出
        $transaction = Yii::$app->db->beginTransaction();
        try {
            [$userChatRecordId, $modelChatRecordId] = \app\models\chat\ChatRecord::saveNewPair(
                $storyId, $chatSessionId, $userId,
                $userPrompt, $generateContentJson
            );
            $transaction->commit();
        }
        catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return [
            'chatSessionInfo' => [
                'id' => $chatSessionId,
                'title' => $chatSession->title,
            ],
            'storyContents' => [
                [
                    'id' => $userChatRecordId,
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                ],
                [
                    'id' => $modelChatRecordId,
                    'role' => 'model',
                    'content' => $generateContents,
                ],
            ],
        ];
    }

    public function getNewStoryFromFrontendProxy($data, $userId, $tempId, $userPrompt)
    {
        if (mb_strlen($tempId) > 100) {
            throw new Exception('前端代理临时ID不能超过100个字符');
        }

        $userPrompt = trim($userPrompt);
        if (mb_strlen($userPrompt) > 200) {
            throw new Exception('用户输入的提示词不能超过200个字符');
        }
        if (!$userPrompt) {
            throw new Exception('用户输入的提示词不能为空');
        }
        // 查看是否包含---、```、换行符
        if (preg_match('/```|---|\n/', $userPrompt)) {
            throw new Exception('用户提示词不符合格式要求');
        }

        $tempRecord = \app\models\FrontendProxyTemp::find()->where([
            'tempId' => $tempId,
        ])->with('chatSession')->one();
        if (!$tempRecord) {
            throw new Exception('未找到前端代理临时记录');
        }
        if (!$tempRecord->chatSession) {
            throw new Exception('未找到会话');
        }
        if ($tempRecord->chatSession->userId != $userId) {
            throw new Exception('会话不属于该用户');
        }
        $chatSession = $tempRecord->chatSession;
        $chatSessionId = $tempRecord->chatSessionId;
        $storyId = $chatSession->storyId;
        $isJson = $tempRecord->isJson == 0 ? false : true;

        if (!is_array($data)) {
            throw new Exception('前端代理响应的内容不是数组');
        }
        $generateContents = yii::$app->LLM->getGeneratedChatContentFromFrontendProxy(
            $data, $isJson
        );
        $generateContentJson = json_encode($generateContents, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->promptHandler->validateGeneratedContent($generateContentJson);

        // 保存用户输入和模型输出
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$tempRecord->delete()) {
                throw new Exception('删除前端代理临时记录失败');
            }
            [$userChatRecordId, $modelChatRecordId] = \app\models\chat\ChatRecord::saveNewPair(
                $storyId, $chatSessionId, $userId,
                $userPrompt, $generateContentJson
            );
            $transaction->commit();
        }
        catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return [
            'chatSessionInfo' => [
                'id' => $chatSessionId,
                'title' => $chatSession->title,
            ],
            'storyContents' => [
                [
                    'id' => $userChatRecordId,
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                ],
                [
                    'id' => $modelChatRecordId,
                    'role' => 'model',
                    'content' => $generateContents,
                ],
            ],
        ];
    }

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
            $chatRecordsArray[] = [
                'id' => $record->id,
                'role' => $record->isUserChat() ? 'user' : 'model',
                'content' => 
                    $record->isUserChat() ?
                    [
                        [
                            'type' => 'user',
                            'content' => $record->contentRecord->content,
                        ],
                    ] :
                    json_decode($record->contentRecord->content, true),
            ];
        }
        return $chatRecordsArray;
    }

    /**
     * 删除一对用户输入、模型输出的记录
     * @param Int $chatRecordId 聊天记录ID
     * @param Int $userId 用户ID
     * @return Void
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
     * 删除某段模型输出中的某个情节的内容
     * @param Int $chatRecordId 聊天记录ID
     * @param Int $userId 用户ID
     * @param Int $itemInx 情节索引
     * @return Array 更新后的该段模型输出的所有情节
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
        $originalContents = json_decode($chatRecord->contentRecord->content, true);
        if (count($originalContents) == 1) {
            throw new Exception('无法删除最后一个内容');
        }
        if (count($originalContents) <= $itemInx) {
            throw new Exception('删除的内容不存在');
        }
        array_splice($originalContents, $itemInx, 1);
        $chatRecord->contentRecord->content = json_encode($originalContents, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!$chatRecord->contentRecord->save()) {
            throw new Exception('保存失败');
        }
        return $originalContents;
    }

    /**
     * 更新某段模型输出中的某个情节的内容
     * @param Int $chatRecordId 聊天记录ID
     * @param Int $userId 用户ID
     * @param Int $itemInx 情节索引
     * @param String $newItemContent 新情节内容
     * @return Array 更新后的该段模型输出的所有情节
     */
    public function editModelContent($chatRecordId, $userId, $itemInx, $newItemContent)
    {
        $newItemContent = trim($newItemContent);
        // 禁止包含换行符
        if (preg_match('/\n/', $newItemContent)) {
            throw new Exception('修改的文本不能包含换行符');
        }
        if (mb_strlen($newItemContent) > 200) {
            throw new Exception('修改的文本不能超过200个字符');
        }
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
        $originalContents = json_decode($chatRecord->contentRecord->content, true);
        if (count($originalContents) <= $itemInx) {
            throw new Exception('修改的内容不存在');
        }
        $originalContents[$itemInx]['content'] = $newItemContent;
        $chatRecord->contentRecord->content = json_encode($originalContents, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!$chatRecord->contentRecord->save()) {
            throw new Exception('保存失败');
        }
        return $originalContents;
    }

    public function getStoriesWithIdAndTitle($userId) {
        $stories = \app\models\Story::find()
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