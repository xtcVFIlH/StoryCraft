<?php

namespace app\components;

use \Exception;
use app\errors\GeneratedContentFormatException;

class LLM extends LLM\LLM 
{

    protected function getRequestBody($prompts, $systemInstruction, $temperature, $topP, $topK)
    {
        return [
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
                'topK' => $topK,
                'responseMimeType' => 'application/json',
            ],    
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => $systemInstruction,
                    ]
                ]
            ]
        ];
    }

    protected function getResponseData($data, $isJson)
    {
        if (!$data) {
            throw new Exception('LLM model response data is empty');
        }
        if (!is_array($data)) {
            $data = json_decode($data, true);
            if (!$data) {
                throw new Exception('LLM model response data is not JSON');
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
            throw new GeneratedContentFormatException();
        }
        return $array;
    }

    protected function getRequestUrl()
    {
        return $this->uri . 'models/gemini-1.5-pro-latest:generateContent?key=' . $this->apiKey;
    }

}