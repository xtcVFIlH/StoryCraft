<?php

namespace app\components\story;

use \Exception;
use \app\errors\GeneratedContentFormatException;
use \app\dto\story\StorySegmentContentItem;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

/**
 * 故事片段
 */
class StorySegment implements \JsonSerializable
{

    /** @var String 故事片段的格式约束提示词 */
    protected static $formatPrompt = <<<prompt
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
prompt;

    /**
     * 获取故事片段的格式约束提示词
     * @return String
     */
    public static function getFormatPrompt()
    {
        return static::$formatPrompt;
    }

    /**
     * 获取放置在提示词末尾的格式约束提示词
     * @return String
     */
    public static function getSuffixFormatPrompt()
    {
        return "\n\n请注意遵守JSON格式要求（若type为action和dialogue，必须包含character字段），并保证输出的JSON数组长度至少为10。";
    }

    /** @var StorySegmentContentItem[] 故事片段内容 */
    protected $content;

    /**
     * 新建实例时会校验故事内容是否符合格式
     * @param String|Array<String, String>[] $content 故事片段内容
     * @throws GeneratedContentFormatException
     */
    function __construct($content)
    {
        // 校验故事内容是否符合格式
        if (is_string($content)) {
            $content = json_decode($content, true);
        }
        try {
            $contentValidator = v::arrayType()->notEmpty();
            $contentValidator->assert($content);

            $contentItemValidator = v::arrayType()
                ->key('type', v::stringType()->notEmpty()->in(['dialogue', 'action', 'description']), true)
                ->key('content', v::stringType()->notEmpty(), true)
                ->when(
                    v::key('type', v::in(['dialogue', 'action'])),
                    v::key('character', v::stringType()->notEmpty(), true),
                    v::alwaysValid()
                );

            foreach ($content as $contentItem) {
                $contentItemValidator->assert($contentItem);
            }
        }
        catch (NestedValidationException $error) {
            $messages = $error->getMessages();
            foreach ($messages as $message) {
                throw new GeneratedContentFormatException($message, 224);
            }
        }

        $this->content = array_map(function ($contentItem) {
            return new StorySegmentContentItem($contentItem['type'], $contentItem['character'] ?? null, $contentItem['content']);
        }, $content);
    }

    /**
     * 获取故事片段内容
     * @return StorySegmentContentItem[]
     */
    public function getContent()
    {
        return $this->content;
    }
    /**
     * 以关联数组格式获取故事片段内容
     * @return Array<String, String>[]
     */
    public function getContentInArray()
    {
        return array_map(function ($contentItem) {
            return $contentItem->toArray();
        }, $this->content);
    }
    /**
     * 以自然语言格式获取故事片段内容
     * @return String
     */
    public function getContentInNaturalLanguage()
    {
        $text = '';
        foreach ($this->content as $contentItem) {
            $contentItem = $contentItem->toArray();
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
     * 以JSON格式获取故事片段内容
     * @return String
     */
    public function getContentInJson()
    {
        return json_encode($this->content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return Array<String, String>[]
     */
    public function jsonSerialize()
    {
        return $this->getContentInArray();
    }

    /**
     * 编辑故事片段内容
     * @param Int $inx 编辑的内容索引
     * @param String $newText 新的内容
     * @throws \Exception 编辑失败时抛出异常
     * @return Void
     */
    public function editContent($inx, $newText)
    {
        if (!isset($this->content[$inx])) {
            throw new Exception('Content index out of range');
        }
        $this->content[$inx]->updateContent($newText);
    }
    /**
     * 删除故事片段内容
     * @param Int $inx 删除的内容索引
     * @throws \Exception 删除失败时抛出异常
     * @return Void
     */
    public function deleteContent($inx)
    {
        if (!isset($this->content[$inx])) {
            throw new Exception('Content index out of range');
        }
        if (count($this->content) <= 1) {
            throw new Exception('Content should have at least one item');
        }
        array_splice($this->content, $inx, 1);
    }
}