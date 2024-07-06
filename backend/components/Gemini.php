<?php

namespace app\components;
use GuzzleHttp\Client;
use \Exception;
use yii;
use app\errors\GeneratedContentFormatException;

class Gemini
{

    protected $uri = 'https://generativelanguage.googleapis.com/v1beta/';
    protected $apiKey;
    protected $proxy;
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

    /** @var \GuzzleHttp\Client */
    protected $client;

    function __construct()
    {
        $this->apiKey = $_ENV['API_KEY'];
        $this->proxy = $_ENV['PROXY'];
        $this->client = new Client([
            'base_uri' => $this->uri,
            'proxy' => $this->proxy,
            'verify' => yii::getAlias('@app') . '/resources/certs/cacert.pem',
        ]);
    }

    /**
     * 获取生成内容时的请求体内容
     * @param Array|Text $prompts 
     * 当为对话交互形式时，为包含历史内容以及新内容的提示词数组: [ ['role' => 'user', 'text' => ''], ['role' => 'model', 'text' => ''] ]；
     * 当为单独生成内容时，为文本内容
     * @param String $systemInstruction 系统提示词，默认为空字符串
     * @param Boolean $isMultiTurn 是否为对话交互形式，默认为false
     * @param Float $temperature 温度，默认为1
     * @param Float|Null $topP topP参数，默认为null
     * @param Float|Null $topK topK参数，默认为null
     */
    public function getGenerateContenctRequestBody(
        $prompts, 
        $systemInstruction = '',
        $isMultiTurn = true,
        $temperature = 1, $topP = null, $topK = null
    )
    {
        return [
            'contents' => [
                $isMultiTurn ? 
                array_map(function ($prompt) {
                    return [
                        'role' => $prompt['role'],
                        'parts' => [
                            [
                                'text' => $prompt['text'],
                            ],
                        ],
                    ];
                }, $prompts) :
                [
                    'parts' => [
                        'text' => $prompts,
                    ],
                ]
            ],
            'safetySettings' => $this->safetySettings,
            'generationConfig' => array_filter([
                'temperature' => $temperature,
                'responseMimeType' => 'application/json',
                'topP' => $topP ?? null,
                'topK' => $topK ?? null,
            ]),
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => $systemInstruction,
                    ]
                ]
            ]
        ];
    }
    /**
     * 获取生成内容时的响应内容
     * @param String|Array $data 响应数据，一个JSON字符串或者关联数组
     * @param Boolean $isJson 生成的文本是否为JSON格式
     * @throws \app\errors\GeneratedContentFormatException
     * @return String|Array 若$isJson为true则返回数组，否则返回字符串
     */
    public function getGenerateContentResponseData($data, $isJson)
    {
        if (!$data) {
            throw new GeneratedContentFormatException('Generated content data is empty');
        }
        if (!is_array($data)) {
            $data = json_decode($data, true);
            if (!$data) {
                throw new GeneratedContentFormatException('Generated content data is not JSON');
            }
        }
        $text = $data['candidates'][0]['content']['parts'][0]['text'];
        if (!$isJson) {
            return $text;
        }
        // 检查是否有json代码块前后缀
        if (preg_match('/```json\n(.*?)\n```/s', $text)) {
            $text = preg_replace('/```json\n(.*?)\n```/s', '$1', $text);
        }
        $array = json_decode($text, true);
        if (!$array) {
            throw new GeneratedContentFormatException('Generated content text is not JSON');
        }
        return $array;
    }
    /**
     * 使用对话交互形式生成内容
     * @param String $modelName 模型名称
     * @param Array $prompts 包含历史内容以及新内容的提示词数组: [ ['role' => 'user', 'text' => ''], ['role' => 'model', 'text' => ''] ]
     * @param String $systemInstruction 系统提示词，默认为空字符串
     * @param Boolean $isJson 生成的文本是否为JSON格式
     * @param Float $temperature 温度，默认为1
     * @return String|Array 若$isJson为true则返回数组，否则返回字符串
     */
    public function generateContentInMultiTurnConversations(
        $modelName,
        $prompts, 
        $systemInstruction = '',
        $isJson = false,
        $temperature = 1
    )
    {
        $response = $this->client->request(
            'POST',
            $this->getGenerateContentPath($modelName),
            [
                'json' => $this->getGenerateContenctRequestBody($prompts, $systemInstruction, true, $temperature),
            ]
        );
        $data = json_decode($response->getBody(), true);
        return $this->getGenerateContentResponseData($data, $isJson);
    }

    /**
     * 获取生成内容的请求URL
     * @param String $modelName 模型名称
     * @return String
     */
    public function getGenerateContentUrl($modelName)
    {
        return $this->uri . $this->getGenerateContentPath($modelName);
    }
    /**
     * 获取生成内容的请求路径
     * @param String $modelName 模型名称
     * @return String
     */
    protected function getGenerateContentPath($modelName)
    {
        return 'models/' . $modelName . ':generateContent?key=' . $this->apiKey;
    }

}