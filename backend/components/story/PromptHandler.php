<?php

namespace app\components\story;

use yii;
use \Exception;
use \errors\GeneratedContentFormatException;

class PromptHandler
{

    protected $systemPrompt = null;
    protected $requirementsPrompt = null;

    function __construct()
    {

$this->systemPrompt =
<<<prompt
你是一名作家，正在与用户进行一个交互式故事创作活动，你和用户作为故事的共同创作人，轮流推进一个故事。
你在用户给出的剧情信息之后续写故事，每次输出的故事内容应包含用户给出的剧情信息，并在此基础上继续推进故事情节。
prompt;  

$this->requirementsPrompt = <<<prompt
故事的额外要求如下:
---

故事内容格式要求:
- 使用JSON格式输出故事内容,使用一个数组包含所有内容
- 对于角色的对话和行为,格式如下:
    * type: 'dialogue' 或 'action'
    * character: '角色的完整名字'
    * content:
      + type为'dialogue'时,content为角色说的话
      + type为'action'时,content为对角色行为的客观记录
- 对于其他所有内容,格式如下:
    * type: 'description'
    * content: 描述的内容
- 输出的数组长度应至少为10
- 在输出中，只能包含故事内容，不能包含任何其他信息

故事内容要求：
- 使用详细的人物语言描写；
- 使用简洁的人物动作描写；
- 尽量不使用环境、场景描写；
- 禁止使用旁白描述剧情进展，一切剧情进展都应通过人物行为、语言来表达；
- 禁止主动询问用户的下一步剧情；
- 禁止直接描述人物的内心活动、情感状态，一切情感状态都应通过人物行为、语言来表达；

---        
prompt;

    }

    /**
     * 校验生成的故事内容是否符合格式要求
     * @param String $content
     * @return Boolean
     */
    public function validateGeneratedContent($content)
    {
        $content = json_decode($content, true);
        if (!is_array($content) || empty($content)) {
            throw new GeneratedContentFormatException('Story content should be an array', 224);
        }
        foreach ($content as $contentItem) {
            if (!is_array($contentItem)) {
                throw new GeneratedContentFormatException('Story content should be an array', 224);
            }
            if (!isset($contentItem['type'])) {
                throw new GeneratedContentFormatException('Story content should have type', 224);
            }
            if (!in_array($contentItem['type'], ['dialogue', 'action', 'description'])) {
                throw new GeneratedContentFormatException('Story content type should be dialogue, action or description', 224);
            }
            if (!isset($contentItem['content'])) {
                throw new GeneratedContentFormatException('Story content should have content', 224);
            }
            if (!is_string($contentItem['content'])) {
                throw new GeneratedContentFormatException('Story content should be a string', 224);
            }
            if ($contentItem['type'] == 'dialogue' || $contentItem['type'] == 'action') {
                if (!isset($contentItem['character'])) {
                    throw new GeneratedContentFormatException('Story content should have character', 224);
                }
                if (!is_string($contentItem['character'])) {
                    throw new GeneratedContentFormatException('Story content character should be a string', 224);
                }
            }
        }
        return true;
    }

    /**
     * 将生成的故事内容转换为自然语言形式，用于向模型发送历史内容
     * @param Array $content
     * @return String
     */
    protected function generatedContentToNaturalLanguage($content)
    {
        $text = '';
        foreach ($content as $contentItem) {
            if ($contentItem['type'] === 'dialogue') {
                $text .= $contentItem['character'] . '说：' . $contentItem['content'] . "\n\n";
            } elseif ($contentItem['type'] === 'action') {
                $text .= $contentItem['character'] . '：（' . $contentItem['content'] . "）\n\n";
            } elseif ($contentItem['type'] === 'description') {
                $text .= '（' . $contentItem['content'] . '）' . "\n\n";
            }
        }
        return $text;
    }

    /**
     * 获取完整的系统提示词
     * @param \app\models\Story $story 故事对象
     * @return String
     */
    protected function getSystemPrompt(
        $story
    )
    {
        $prompt = $this->systemPrompt;
        $storyInfoPrompt = $story->getStoryInfoPrompt();
        return $prompt . "\n\n" . $storyInfoPrompt;
    }

    /**
     * 获取完整的用户提示词
     * @return String
     */
    protected function getUserPrompt($userPrompt)
    {
        $prompt = "新的剧情信息如下:\n---\n\n" . $userPrompt . "\n\n---";
        $prompt .= "\n\n" . $this->requirementsPrompt;
        return $prompt;
    }

    /**
     * 获取完整的提交给模型的提示词
     * @param \app\models\Story $story 故事对象
     * @param string $chatSessionId 会话ID
     * @param string $userInputPrompt 新故事文本
     * @return Array[] 提示词数组: [ ['role' => 'user', 'text' => ''], ['role' => 'model', 'text' => ''] ] 
     */
    public function getPrompts(
        $story,
        $chatSessionId,
        $userInputPrompt
    )
    {
        $prompts = [
            [
                'role' => 'user',
                'text' => $this->getSystemPrompt($story),
            ],
            [
                'role' => 'model',
                'text' => '我理解了，我将遵循以上所有要求进行创作。请您给出第一个剧情信息。',
            ],
        ];
        // 获取历史会话
        $chatRecords = \app\models\chat\ChatRecord::find()
            ->where([
                'chatSessionId' => $chatSessionId,
                'storyId' => $story->id,
            ])
            ->with('contentRecord')
            ->orderBy('id ASC')
            ->all();
        foreach ($chatRecords as $record) {
            if (!$record->contentRecord) {
                throw new Exception('content record not found');
            }
            $prompts []= [
                'role' => $record->isUserChat() ? 'user' : 'model',
                'text' => $record->isUserChat() ? $record->contentRecord->content : $this->generatedContentToNaturalLanguage(json_decode($record->contentRecord->content, true)),
            ];
        }
        $prompts []= [
            'role' => 'user',
            'text' => $this->getUserPrompt($userInputPrompt),
        ];
        return $prompts;
    }

}