<?php

namespace app\components\story;

use \Exception;

class ExtractKeypointsPromptHandler
{

    protected $systemPrompt = null;

    function __construct()
    {
$this->systemPrompt = <<<prompt
你是一名负责总结文章的人员。用户会提供一段故事，以及对应的故事背景、角色设定等内容。
你需要尽可能地详细总结这段故事的内容，要求你的总结能够完美概括该故事片段的内容，能够体现出角色状态（衣着、情绪、境况等）的变化。
你需要将总结内容使用Bullet List输出。
除了总结内容，你不需要输出多余信息。
prompt;
    }

    /**
     * 获取完整的系统提示词
     * @param \app\models\Story $story 故事实例
     * @param String|Null $customInstructions 额外提示词
     * @return String
     */
    protected function getSystemPrompt($story, $customInstructions = null)
    {
        $prompt = $this->systemPrompt;
        $prompt .= "\n\n" . $story->getStoryInfoPrompt();
        if ($customInstructions) {
            $prompt .= "\n\n额外的故事相关信息:\n---\n\n" . $customInstructions . "\n\n---";
        }
        return $prompt;
    }

    /**
     * 获取用于发送给模型的相关内容
     * @param \app\models\Story $story 故事实例
     * @param String $chatSessionId 会话ID
     * @param String|Null $customInstructions 额外提示词
     * @throws Exception 当获取历史故事内容失败时抛出异常
     * @return Array{
     *   userPrompt: String,
     *   systemInstructions: String
     * }|Null 若之前没有历史记录则返回null
     */
    public function getPrompts(
        $story,
        $chatSessionId,
        $customInstructions = null
    )
    {
        $chatRecords = \app\models\chat\ChatRecord::find()
            ->where([
                'chatSessionId' => $chatSessionId,
                'storyId' => $story->id
            ])
            ->with('contentRecord')
            ->orderBy('id ASC')
            ->all();
        if (!$chatRecords) {
            return null;
        }
        $userPrompt = '';
        foreach ($chatRecords as $chatRecord) {
            if (!$chatRecord->contentRecord) {
                continue;
            }
            if ($userPrompt !== '') {
                $userPrompt .= "\n\n";
            }
            if ($chatRecord->isUserChat()) {
                $userPrompt .= '（' . $chatRecord->contentRecord->content . '）';
            }
            else {
                $storySegment = new StorySegment($chatRecord->contentRecord->content);
                $userPrompt .= $storySegment->getContentInNaturalLanguage();
            }
        }
        return [
            'userPrompt' => $userPrompt,
            'systemInstructions' => $this->getSystemPrompt($story, $customInstructions)
        ];
    }

}