<?php

namespace app\controllers;

use yii;
use \Exception;

class StoryController extends \yii\web\Controller
{

    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // 判断header中的Content-Type
        $contentType = Yii::$app->request->getHeaders()->get('Content-Type');
        if (!str_contains($contentType, 'application/json')) {
            throw new \yii\web\HttpException(400, 'Content-Type must be application/json');
        }

        return true;
    }

    public function actionGetStoryInfo()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }

        $postBody = json_decode(Yii::$app->request->getRawBody(), true);

        if (!isset($postBody['storyId'])) {
            throw new Exception('storyId cannot be empty');
        }
        $storyId = $postBody['storyId'];

        $story = \app\models\Story::findOne($storyId);
        if (!$story) {
            throw new Exception('story not found: ' . $storyId);
        }
        
        return [
            'title' => $story->title,
            'backgroundInfo' => $story->backgroundInfo,
            'characterInfos' => $story->characters,
        ];
    }

    public function actionGetAllChatSessions()
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

    public function actionGenerate()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }
        $postBody = json_decode(Yii::$app->request->getRawBody(), true);

        if (!isset($postBody['userPrompt']) || trim($postBody['userPrompt']) === '') {
            throw new Exception('userPrompt cannot be empty');
        }
        if (!isset($postBody['storyId']) || $postBody['storyId'] === '' || $postBody['storyId'] === null) {
            throw new Exception('storyId cannot be empty');
        }    

        $data = Yii::$app->story->getNewStory(
            yii::$app->user->id, $postBody['userPrompt'], $postBody['storyId'], isset($postBody['chatSessionId']) ? $postBody['chatSessionId'] : null
        );

        return $data;
    }

    public function actionGetAllStoryContents()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }
        $postBody = json_decode(Yii::$app->request->getRawBody(), true);

        if (!isset($postBody['storyId'])) {
            throw new Exception('storyId cannot be empty');
        }
        if (!isset($postBody['chatSessionId'])) {
            throw new Exception('chatSessionId cannot be empty');
        }

        $data = Yii::$app->story->getAllStoryContents(yii::$app->user->id, $postBody['storyId'], $postBody['chatSessionId']);

        return $data;
    }

    public function actionDeleteUserStoryContent()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }
        $postBody = json_decode(Yii::$app->request->getRawBody(), true);

        if (!isset($postBody['recordId'])) {
            throw new Exception('recordId cannot be empty');
        }
        $userId = yii::$app->user->id;

        return [
            'deletedRecordIds' => yii::$app->story->deleteChatRecordPair($postBody['recordId'], $userId),
        ];
    }

    public function actionGetStoriesWithIdAndTitle()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }
        $userId = yii::$app->user->id;

        return [
            'storiesWithIdAndTitle' => yii::$app->story->getStoriesWithIdAndTitle($userId),
        ];
    }

    public function actionEditModelStoryContent()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }
        $userId = yii::$app->user->id;

        $postBody = json_decode(Yii::$app->request->getRawBody(), true);

        // chatRecordID
        if (!isset($postBody['chatRecordId'])) {
            throw new Exception('chatRecordId cannot be empty');
        }
        $chatRecordId = $postBody['chatRecordId'];
        if (!preg_match('/^\d{1,8}$/', $chatRecordId)) {
            throw new Exception('chatRecordId format error');
        }
        // itemInx
        if (!isset($postBody['itemInx'])) {
            throw new Exception('itemInx cannot be empty');
        }
        $itemInx = $postBody['itemInx'];
        if (!preg_match('/^\d{1,8}$/', $itemInx)) {
            throw new Exception('itemInx format error');
        }
        // newItemContent
        if (!isset($postBody['newItemContent'])) {
            throw new Exception('newItemContent cannot be empty');
        }
        $newItemContent = $postBody['newItemContent'];
        if (!is_string($newItemContent)) {
            throw new Exception('newItemContent should be a string');
        }

        return [
            'newContents' => yii::$app->story->editModelContent($chatRecordId, $userId, $itemInx, $newItemContent),
        ];
    }

    public function actionDeleteModelStoryContent()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }
        $userId = yii::$app->user->id;

        $postBody = json_decode(Yii::$app->request->getRawBody(), true);

        // chatRecordID
        if (!isset($postBody['chatRecordId'])) {
            throw new Exception('chatRecordId cannot be empty');
        }
        $chatRecordId = $postBody['chatRecordId'];
        if (!preg_match('/^\d{1,8}$/', $chatRecordId)) {
            throw new Exception('chatRecordId format error');
        }
        // itemInx
        if (!isset($postBody['itemInx'])) {
            throw new Exception('itemInx cannot be empty');
        }
        $itemInx = $postBody['itemInx'];
        if (!preg_match('/^\d{1,8}$/', $itemInx)) {
            throw new Exception('itemInx format error');
        }

        return [
            'newContents' => yii::$app->story->deleteModelContent($chatRecordId, $userId, $itemInx),
        ];
    }

    public function actionDeleteChatSession()
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

}