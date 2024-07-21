<?php

namespace app\dto\story;

class StoryContent implements \JsonSerializable
{
    /** @var Int */
    protected $id;
    /** @var String */
    protected $role;
    /** @var Array{
     *   type: String,
     *   content: String
     * }[]
     */
    protected $content;

    /**
     * @param Int $id
     * @param String $content
     * @return StoryContent
     */
    public static function newUserContent($id, $content)
    {
        $instance = new static();
        $instance->id = $id;
        $instance->role = 'user';
        $instance->content = [
            [
                'type' => 'user',
                'content' => $content
            ],
        ];
        return $instance;
    }
    /**
     * @param Int $id
     * @param \app\components\story\StorySegment $storySegment
     * @return StoryContent
     */
    public static function newModelContent($id, $storySegment)
    {
        $instance = new static();
        $instance->id = $id;
        $instance->role = 'model';
        $instance->content = $storySegment->getContentInArray();
        return $instance;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content
        ];
    }
}