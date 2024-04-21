<?php

namespace app\components\SYSTEM;

use Yii;
use yii\web\Response;

class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * 不再使用异常处理页面，而是将异常实例交给response的onsend事件处理
     */
    protected function renderException($exception)
    {
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
            $response->isSent = false;
            $response->stream = null;
            $response->data = null;
            $response->content = null;
        } else {
            $response = new Response();
        }

        $response->data = $exception;
        $response->setStatusCodeByException($exception);

        $response->send();

    }
}
