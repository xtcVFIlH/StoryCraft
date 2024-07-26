<?php

namespace app\components\story;

use yii;
use \Exception;
use app\errors\GeneratedContentFormatException;

class StoryPromptHandler
{

    protected $systemPrompt = null;
    protected $requirementsPrompt = null;

    function __construct()
    {

$this->systemPrompt =
<<<prompt
你是一名作家，正在与用户进行一个交互式故事创作活动，你和用户作为故事的共同创作人，轮流推进一个故事。
prompt;  

$storySegmentFormatPrompt = StorySegment::getFormatPrompt();

$this->requirementsPrompt = <<<prompt
故事的额外要求如下:
---

故事内容格式要求:
$storySegmentFormatPrompt

故事内容要求：
- 在用户给出的剧情信息的基础上，续写之后大约5分钟内的剧情；
- 详细的人物语言描写和人物动作描写；
- 禁止主动询问用户下一步剧情；
- 禁止使用旁白；

---        
prompt;

    }

    /**
     * 获取完整的系统提示词
     * @param \app\models\Story $story 故事对象
     * @param String|Null $customInstructions 额外提示词
     * @return String
     */
    protected function getSystemPrompt(
        $story, $customInstructions = null
    )
    {
        $prompt = $this->systemPrompt;
        $prompt .= "\n\n" . $this->requirementsPrompt;
        $storyInfoPrompt = $story->getStoryInfoPrompt();
        $prompt .= "\n\n" . $storyInfoPrompt;
        if ($customInstructions) {
            $prompt .= "\n\n额外的背景信息或要求:\n---\n\n" . $customInstructions . "\n\n---";
        }
        return $prompt;
    }

    /**
     * 获取完整的用户提示词
     * @param String $userPrompt 用户输入的新故事文本
     * @param String|Null $extractedKeypoints 提取的历史故事内容关键点
     * @return String
     */
    protected function getUserPrompt($userPrompt, $extractedKeypoints = null)
    {
        $prompt = $extractedKeypoints ? "之前的故事内容梗概:\n---\n\n$extractedKeypoints\n\n---\n\n" : '';
        $prompt .= "新的剧情信息如下:\n---\n\n" . $userPrompt . "\n\n---";
        $prompt .= StorySegment::getSuffixFormatPrompt();
        return $prompt;
    }

    /**
     * 获取完整的提交给模型的提示词
     * @param \app\models\Story $story 故事对象
     * @param String $chatSessionId 会话ID
     * @param String $userInputPrompt 新故事文本
     * @param String|Null $customInstructions 额外提示词
     * @param String|Null $extractedKeypoints 提取的历史故事内容关键点
     * @return Array{
     *   user: \app\dto\gemini\MultiTurnConversations,
     *   system: String
     * } 用户提示词和系统提示词
     */
    public function getPrompts(
        $story,
        $chatSessionId,
        $userInputPrompt,
        $customInstructions = null,
        $extractedKeypoints = null
    )
    {
        // 获取历史会话
        $chatRecords = \app\models\chat\ChatRecord::find()
            ->where([
                'chatSessionId' => $chatSessionId,
                'storyId' => $story->id,
            ])
            ->with('contentRecord')
            ->orderBy('id ASC')
            ->all();
        $userPrompts = new \app\dto\gemini\MultiTurnConversations;
        foreach ($chatRecords as $record) {
            if (!$record->contentRecord) {
                throw new Exception('content record not found');
            }
            if ($record->isUserChat()) {
                $userPrompts->pushUserChat($record->contentRecord->content);
            }
            else {
                $storySegment = new StorySegment($record->contentRecord->content);
                $userPrompts->pushModelChat($storySegment->getContentInNaturalLanguage());
            }
        }
        $userPrompts->pushUserChat($this->getUserPrompt($userInputPrompt, $extractedKeypoints));
        return [
            'user' => $userPrompts,
            'system' => $this->getSystemPrompt($story, $customInstructions),
        ];
    }

}