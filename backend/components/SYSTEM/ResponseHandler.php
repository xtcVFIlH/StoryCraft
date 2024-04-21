<?php

namespace app\components\SYSTEM;

use Yii;
use yii\web\Response;
use yii\base\UserException;

class ResponseHandler extends \yii\web\Response
{

    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_SEND, [$this, 'beforeSend']);
    }

    public function beforeSend($event)
    {
        // 设置响应格式和编码
        $response = $event->sender;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->charset = 'UTF-8';

        // 允许跨域请求  
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');      

        // 设置响应格式
        if ($response->data !== null) {
            if ($response->data instanceof \Throwable) {
                if ($response->data instanceof \errors\GeneratedContentFormatException) {
                    $responseData = [
                        'code' => 7750,
                        'message' => '模型生成内容不符合格式要求: ' . $response->data->getMessage(),
                    ];
                }
                else if ($response->data instanceof \yii\db\StaleObjectException) {
                    $responseData = [
                        'code' => 7749,
                        'message' => '数据已过期',
                    ];
                }
                else {
                    $responseData = [
                        'code' => $response->data->getCode() ? $response->data->getCode() : 500,
                        'message' => $response->data->getMessage() ?? '服务器内部错误',
                    ];
                }
            }
            else {
                $responseData = [
                    'code' => 0,
                    'data' => $response->data,
                ];
            }
            $response->data = $responseData;
            // 强制将http状态码设置为200
            $response->setStatusCode(200);
        }
    }

}