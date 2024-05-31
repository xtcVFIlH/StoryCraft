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

    public function actionUpdateGeneratedContentFromFrontendProxy()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }
        $postBody = json_decode(Yii::$app->request->getRawBody(), true);

        if (!isset($postBody['data'])) {
            throw new Exception('data cannot be empty');
        }

        if (!isset($postBody['tempId'])) 
        {
            throw new Exception('tempId cannot be empty');
        }

        if (!isset($postBody['userPrompt'])) 
        {
            throw new Exception('userPrompt cannot be empty');
        }

        $data = yii::$app->story->getNewStoryFromFrontendProxy(
            $postBody['data'], yii::$app->user->id, $postBody['tempId'], $postBody['userPrompt']
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

}