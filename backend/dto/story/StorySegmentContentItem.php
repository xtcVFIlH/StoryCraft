<?php

namespace app\dto\story;

class StorySegmentContentItem implements \JsonSerializable
{
    /** @var String */
    protected $type;
    /** @var String|Null */
    protected $character;
    /** @var String */
    protected $content;

    function __construct($type, $character, $content)
    {
        $this->type = $type;
        $this->character = $character;
        $this->content = $content;
    }

    /**
     * @return Array<String, String>
     */
    public function toArray()
    {
        $array = [
            'type' => $this->type,
            'content' => $this->content,
        ];
        if ($this->character) {
            $array['character'] = $this->character;
        }
        return $array;
    }

    /**
     * @return Array<String, String>
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @var String $type
     */
    public function updateContent($content)
    {
        $this->content = $content;
    }
}