<?php

namespace app\components\story;

use yii;
use \Exception;
use app\errors\GeneratedContentFormatException;

class PromptHandler
{

    protected $systemPrompt = null;
    protected $requirementsPrompt = null;

    function __construct()
    {

$this->systemPrompt =
<<<prompt
你是一名作家，正在与用户进行一个交互式故事创作活动，你和用户作为故事的共同创作人，轮流推进一个故事。
prompt;  

$this->requirementsPrompt = <<<prompt
故事的额外要求如下:
---

故事内容格式要求:
- 使用JSON格式输出故事内容,使用一个数组包含所有内容
- 角色对话的格式如下：
```json
{
    "type": "dialogue",
    "character": "角色的完整名字",
    "content": "角色说的话"
}
```
- 角色行为的格式如下：
```json
{
    "type": "action",
    "character": "角色的完整名字",
    "content": "角色行为的描述"
}
```
- 对于其他所有内容,格式如下:
```json
{
	"type": "description",
	"content": "描述的内容"
}
```

故事内容要求：
- 在用户给出的剧情信息的基础上，续写之后大约5分钟内的剧情；
- 详细的人物语言描写和人物动作描写；
- 禁止主动询问用户下一步剧情；
- 禁止使用旁白；

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
     * @return String
     */
    protected function getUserPrompt($userPrompt)
    {
        $prompt = "新的剧情信息如下:\n---\n\n" . $userPrompt . "\n\n---";
        $prompt .= "\n请注意遵守JSON格式要求（若type为action和dialogue，必须包含character字段），并保证输出的JSON数组长度至少为10。";
        return $prompt;
    }

    /**
     * 获取完整的提交给模型的提示词
     * @param \app\models\Story $story 故事对象
     * @param String $chatSessionId 会话ID
     * @param String $userInputPrompt 新故事文本
     * @param String|Null $customInstructions 额外提示词
     * @return Array[] 用户提示词数组和系统提示词: ['user' => ['role' => 'user', 'text' => ''], ['role' => 'model', 'text' => ''], 'system' => '', ] 
     */
    public function getPrompts(
        $story,
        $chatSessionId,
        $userInputPrompt,
        $customInstructions = null
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
        return [
            'user' => $prompts,
            'system' => $this->getSystemPrompt($story, $customInstructions),
        ];
    }

}