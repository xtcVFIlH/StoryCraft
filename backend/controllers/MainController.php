<?php

namespace app\controllers;

use yii;
use \Exception;

class MainController extends \yii\web\Controller
{

    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // 判断header中的Content-Type
        $contentType = Yii::$app->request->getHeaders()->get('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            throw new \yii\web\HttpException(400, 'Content-Type must be application/json');
        }

        return true;
    }

}