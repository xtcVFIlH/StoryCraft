<?php

namespace app\components;

use yii;
use \Exception;

class User extends \yii\web\User
{

    public function init ()
    {
        parent::init();
        try {
            $accessToken = Yii::$app->request->get('token');
            if (preg_match('/^[a-zA-Z0-9]{1,10}$/', $accessToken)) {
                $this->loginByAccessToken($accessToken);
            }
        }
        catch (\Throwable $e) {
            // Do nothing
        }
    }

}