<?php

namespace app\components;

use GuzzleHttp\Client;
use \Exception;
use Yii;

class LLM 
{

    public $apiKey;
    public $uri;
    public $proxy;

    protected $modelName = 'gemini-1.5-pro-latest';

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
     * 获取以对话形式生成的内容
     * @param Array $prompts 包含历史内容以及新内容的提示词数组: [ ['role' => 'user', 'text' => ''], ['role' => 'model', 'text' => ''] ]
     * @param Boolean $isJson 生成的文本是否为JSON格式
     * @return Boolean|String|Array 获取失败时返回false，若$isJson为true则返回数组，否则返回字符串
     */
    public function generateChatContent($prompts, $isJson = false, $temperature = 1.0, $topP = 1.0)
    {
        try {
            if (!$this->apiKey) {
                throw new Exception('API key not set');
            }
            $client = new Client([
                'base_uri' => $this->uri,
                'proxy' => $this->proxy,
                'verify' => Yii::getAlias('@app') . '/resources/certs/cacert.pem',
            ]);
            $response = $client->request('POST', 'models/' . $this->modelName . ':generateContent?key=' . $this->apiKey, [
                'json' => [
                    'contents' => [
                        array_map(function ($prompt) {
                            return [
                                'role' => $prompt['role'],
                                'parts' => [
                                    [
                                        'text' => $prompt['text'],
                                    ],
                                ],
                            ];
                        }, $prompts),
                    ],
                    'safetySettings' => $this->safetySettings,
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'topP' => $topP,
                    ],    
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            if (!$data) {
                throw new Exception('Failed to generate freeform text');
            }
            $text = $data['candidates'][0]['content']['parts'][0]['text']; 
            if ($isJson) {
                // 移除markdown的json代码块前后缀
                $text = preg_replace('/```json\n(.*?)\n```/s', '$1', $text);
                $array = json_decode($text, true);
                if (!$array) {
                    throw new \errors\GeneratedContentFormatException('Failed to decode JSON');
                }
                return $array;
            }
            else {
                return $text;
            }
        }
        catch (\Throwable $e) {
            throw $e;
        }
    }

}