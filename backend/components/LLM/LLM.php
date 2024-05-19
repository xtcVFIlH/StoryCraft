<?php

namespace app\components\LLM;

use GuzzleHttp\Client;
use \Exception;
use Yii;

abstract class LLM
{

    public $apiKey;
    public $uri;
    public $proxy;

    protected $safetySettings = [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_NONE',
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_NONE',
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_NONE',
        ],
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_NONE',
        ],
    ];

    /**
     * 将数据转换为请求体
     * @param Array $prompts 包含历史内容以及新内容的提示词数组: [ ['role' => 'user', 'text' => ''], ['role' => 'model', 'text' => ''] ]
     * @return Array
     */
    abstract protected function getRequestBody($prompts, $temperature, $topP);

    /**
     * 从响应数据中提取内容
     * @param String|Array $data 响应数据
     * @param Boolean $isJson 生成的文本是否为JSON格式
     * @return String|Array 若$isJson为true则返回数组，否则返回字符串
     * @throws \app\errors\GeneratedContentFormatException
     * @throws \Exception
     */
    abstract protected function getResponseData($data, $isJson);

    /**
     * 获取请求的URL
     * @return String
     */
    abstract protected function getRequestUrl();

    /**
     * 获取以对话形式生成的内容
     * @param Array $prompts 包含历史内容以及新内容的提示词数组: [ ['role' => 'user', 'text' => ''], ['role' => 'model', 'text' => ''] ]
     * @param Boolean $isJson 生成的文本是否为JSON格式
     * @return String|Array 若$isJson为true则返回数组，否则返回字符串
     * @throws \Throwable
     */
    public function generateChatContent($prompts, $isJson = false, $temperature = 1.0, $topP = 1.0)
    {
        try {
            if (!$this->apiKey) {
                throw new Exception('API key not set');
            }
            $client = new Client([
                'proxy' => $this->proxy,
                'verify' => Yii::getAlias('@app') . '/resources/certs/cacert.pem',
            ]);
            $response = $client->request('POST', $this->getRequestUrl(), [
                'json' => $this->getRequestBody($prompts, $temperature, $topP),
            ]);
            $data = json_decode($response->getBody(), true);
            return $this->getResponseData($data, $isJson);
        }
        catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * 通过前端代理生成时，需要发送的内容
     * @param Array $prompts 包含历史内容以及新内容的提示词数组: [ ['role' => 'user', 'text' => ''], ['role' => 'model', 'text' => ''] ]
     * @param Boolean $isJson 生成的文本是否为JSON格式
     * @return Array
     */
    public function getRequestDataFrontendProxy($prompts, $isJson = false, $temperature = 1.0, $topP = 1.0)
    {
        if (!$this->apiKey) {
            throw new Exception('API key not set');
        }
        return [
            'url' => $this->getRequestUrl(),
            'json' => $this->getRequestBody($prompts, $temperature, $topP),
            'query' => [],
            'tempId' => substr(md5(uniqid()), 0, 10),
        ];
    }

    /**
     * 将前端代理返回的数据提取出来
     * @return Array
     */
    public function getGeneratedChatContentFromFrontendProxy($data, $isJson = false)
    {
        return $this->getResponseData($data, $isJson);
    }

}