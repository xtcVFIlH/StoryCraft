<?php

namespace app\components;

use yii;
use \Exception;

class FrontendProxy 
{

    /** @var Boolean 当前请求实例是否使用前端代理 */
    public $proxyUsed = false;

    /** @var String 当前请求实例的前端代理ID */
    protected $frontendProxyId = '';

    function __construct()
    {
        $this->proxyUsed = !!yii::$app->params['usingFrontendProxy'];
        if ($this->proxyUsed) {
            if (!yii::$app->request->get('frontend_proxy_id')) {
                throw new \yii\web\HttpException(400, 'frontend_proxy_id is required');
            }
            $this->frontendProxyId = yii::$app->request->get('frontend_proxy_id');
        }
    }

    /**
     * 保存请求数据到缓存中
     * @param String $url
     * @param Array $json
     * @return Boolean 是否保存成功
     */
    protected function setRequestData($url, $json)
    {
        $cacheKey = 'request-' . $this->frontendProxyId;
        return Yii::$app->cache->set($cacheKey, [
            'url' => $url,
            'json' => $json
        ]);
    }

    /**
     * 获取缓存中对应ID的请求数据
     * @return Array{
     *   url: String,
     *   json: Array
     * }|Boolean 返回false时表示请求数据不存在、或在获取后删除失败
     */
    public function getRequestData()
    {
        $cacheKey = 'request-' . $this->frontendProxyId;
        $requestData = Yii::$app->cache->get($cacheKey);
        if ($requestData !== false) {
            if (!Yii::$app->cache->delete($cacheKey)) {
                return false;
            }
        }
        return $requestData;
    }

    /**
     * 设置缓存中对应ID的请求响应数据
     * @param Mixed $response
     * @return Boolean 是否设置成功
     */
    public function setResponseData($response)
    {
        $cacheKey = 'response-' . $this->frontendProxyId;
        return Yii::$app->cache->set($cacheKey, $response);
    }

    /**
     * 获取缓存中对应ID的请求响应数据
     * @return Mixed|Boolean 返回false时表示请求响应数据不存在、或在获取后删除失败
     */
    protected function getResponseData()
    {
        $cacheKey = 'response-' . $this->frontendProxyId;
        $responseData = Yii::$app->cache->get($cacheKey);
        if ($responseData !== false) {
            if (!Yii::$app->cache->delete($cacheKey)) {
                return false;
            }
        }
        return $responseData;
    }

    /**
     * 发送前端代理的POST请求
     * @param String $url
     * @param Array $json
     * @throws Exception
     * @return Array|Boolean 请求成功时返回响应数据，否则返回false
     */
    public function post($url, $json)
    {
        if (!$this->setRequestData($url, $json)) {
            throw new Exception('Failed to set frontend proxy request data');
        }
        yii::$app->cache->delete('response-' . $this->frontendProxyId);
        $retryCount = 25;
        while ($retryCount--) {
            $response = $this->getResponseData();
            if ($response !== false) {
                return $response;
            }
            sleep(1);
        }
        return false;
    }

}