<?php

namespace app\dto\gemini;

/**
 * gemini的多轮对话
 */
class MultiTurnConversations
{
    /**
     * @var Array{
     *   role: 'user'|'model',
     *   text: String
     * } 对话内容列表
     */
    protected $chats = [];

    /**
     * 获取对话内容列表
     * @return Array{
     *   role: 'user'|'model',
     *   text: String
     * } 对话内容列表
     */
    public function getChats()
    {
        return $this->chats;
    }

    /**
     * 添加用户对话内容
     * @param String $text
     * @return Void
     */
    public function pushUserChat($text)
    {
        $this->chats[] = [
            'role' => 'user',
            'text' => $text,
        ];
    }

    /**
     * 添加模型对话内容
     * @param String $text
     * @return Void
     */
    public function pushModelChat($text)
    {
        $this->chats[] = [
            'role' => 'model',
            'text' => $text,
        ];
    }
}