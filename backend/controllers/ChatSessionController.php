<?php

namespace app\controllers;

use yii;
use \Exception;

class ChatSessionController extends base\Controller 
{

    public $enableCsrfValidation = false;

    public function actionUpdate()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }

        $postBody = json_decode(Yii::$app->request->getRawBody(), true);

        if (!isset($postBody['storyId'])) {
            throw new Exception('storyId cannot be empty');
        }
        if (!isset($postBody['title'])) {
            throw new Exception('title cannot be empty');
        }

        $chatSessionId = isset($postBody['chatSessionId']) ? $postBody['chatSessionId'] : null;
        $storyId = $postBody['storyId'];
        $title = trim($postBody['title']);
        $customInstructions = isset($postBody['customInstructions']) ? trim($postBody['customInstructions']) : '';

        if (mb_strlen($title) > 200) {
            throw new Exception('title length should be less than 200');
        }
        if (mb_strlen($customInstructions) > 10000) {
            throw new Exception('customInstructions is tooooo long');
        }

        if ($chatSessionId !== null) {
            $record = \app\models\chat\ChatSession::find()
                ->where(['id' => $chatSessionId, 'storyId' => $storyId, 'userId' => yii::$app->user->id])
                ->one();
        }
        else {
            $record = new \app\models\chat\ChatSession();
            $record->storyId = $storyId;
            $record->userId = yii::$app->user->id;
        }

        if (!$record) {
            throw new Exception('chatSession not found');
        }

        if ($record->title != $title || $record->customInstructions != $customInstructions) {
            $record->title = $title;
            $record->customInstructions = $customInstructions;
            if (!$record->save()) {
                throw new Exception('update failed');
            }
        }

        return [
            'chatSessionId' => $record->id,
            'title' => $record->title,
            'customInstructions' => $record->customInstructions,
        ];
    }

    public function actionDelete()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }
        $userId = yii::$app->user->id;

        $postBody = json_decode(Yii::$app->request->getRawBody(), true);

        // chatSessionId
        if (!isset($postBody['chatSessionId'])) {
            throw new Exception('chatSessionId cannot be empty');
        }
        $chatSessionId = $postBody['chatSessionId'];
        if (!preg_match('/^\d{1,8}$/', $chatSessionId)) {
            throw new Exception('chatSessionId format error');
        }
        // storyId
        if (!isset($postBody['storyId'])) {
            throw new Exception('storyId cannot be empty');
        }
        $storyId = $postBody['storyId'];
        if (!preg_match('/^\d{1,8}$/', $storyId)) {
            throw new Exception('storyId format error');
        }

        yii::$app->story->deleteChatSession($storyId, $chatSessionId, $userId);

        return [];
    }

    public function actionGetOne()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }

        $postBody = json_decode(Yii::$app->request->getRawBody(), true);

        if (!isset($postBody['chatSessionId'])) {
            throw new Exception('chatSessionId cannot be empty');
        }
        if (!isset($postBody['storyId'])) {
            throw new Exception('storyId cannot be empty');
        }
        $chatSessionId = $postBody['chatSessionId'];
        $storyId = $postBody['storyId'];
        $userId = yii::$app->user->id;

        $chatSession = \app\models\chat\ChatSession::find()
            ->select(['id', 'title', 'customInstructions'])
            ->where(['id' => $chatSessionId, 'storyId' => $storyId, 'userId' => $userId])
            ->one();

        return [
            'chatSessionId' => $chatSession->id,
            'title' => $chatSession->title,
            'customInstructions' => $chatSession->customInstructions,
        ];
    }

    public function actionGetAll()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }

        $postBody = json_decode(Yii::$app->request->getRawBody(), true);

        if (!isset($postBody['storyId'])) {
            throw new Exception('storyId cannot be empty');
        }
        $storyId = $postBody['storyId'];
        $userId = yii::$app->user->id;

        $chatSessions = \app\models\chat\ChatSession::find()
            ->select(['id', 'title'])
            ->where(['storyId' => $storyId, 'userId' => $userId])
            ->orderBy('id DESC')
            ->all();
        
        return [
            'chatSessions' => array_map(function($record) {
                return [
                    'id' => $record->id,
                    'title' => $record->title,
                ];
            }, $chatSessions),
        ];
    }

}