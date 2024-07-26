<?php

namespace app\controllers;

use yii;
use \Exception;

class FrontendProxyController extends base\Controller
{

    /**
     * 检查当前系统是否配置了前端代理
     * @return Boolean
     */
    public function actionIsUsed()
    {
        return yii::$app->frontendProxy->proxyUsed;
    }

    /**
     * 使用长轮询等待后端需要代理的请求，所有请求都是POST请求
     * @return Array{
     *   url: String,
     *   json: Array
     * }|false 当在未找到请求时返回false
     */
    public function actionWaitRequest()
    {        
        $retryCount = 25;
        while($retryCount--) {
            $request = yii::$app->frontendProxy->getRequestData();
            if ($request !== false) {
                return $request;
            }
            sleep(1);
        }
        return false;
    }

    /**
     * 将前端代理的请求响应数据发送回后端
     * @bodyParam response Array<String, Mixed> 响应数据
     * @return Boolean 是否设置成功
     */
    public function actionSetResponse()
    {
        $postBody = json_decode(Yii::$app->request->getRawBody(), true);
        if (!isset($postBody['response_data'])) {
            throw new \yii\web\HttpException(400, 'response_data is required');
        }
        $response = $postBody['response_data'];
        return yii::$app->frontendProxy->setResponseData($response);
    }

}