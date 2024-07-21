<?php

namespace app\controllers;

use yii;
use yii\web\UploadedFile;
use \Exception;

class UploadController extends \yii\web\Controller
{

    public $enableCsrfValidation = false;

    protected $requestdata = [];

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // 允许的Content-Type:
        // - application/json
        // - multipart/form-data
        $contentType = Yii::$app->request->getHeaders()->get('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            $this->requestdata = json_decode(Yii::$app->request->getRawBody(), true);
        }
        else if (str_contains($contentType, 'multipart/form-data')) {
            $this->requestdata = Yii::$app->request->post();
        }
        else {
            throw new Exception('Content-Type must be application/json or multipart/form-data');
        }

        return true;
    }

    /**
     * 更新故事信息
     * @bodyParam storyId int 故事ID (如果不传则新增)
     * @bodyParam storyInfo Array required 故事信息
     * @return Array{
     *   storyId: Int,
     *   storiesWithIdAndTitle: Array{
     *     id: Int,
     *     title: String
     *   }[]
     * }
     */
    public function actionStoryInfo()
    {
        if (yii::$app->user->isGuest) {
            throw new Exception('User not logged in');
        }
        $postBody = $this->requestdata;

        $userId = yii::$app->user->id;
        
        $storyId = isset($postBody['storyId']) ? $postBody['storyId'] : null;
        if (!isset($postBody['storyInfo'])) {
            throw new Exception('StoryInfo cannot be empty');
        }

        $storyInfoDto = new \app\dto\story\StoryInfo($postBody['storyInfo']);
        $storyId = Yii::$app->story->updateStory($storyInfoDto, $userId, $storyId);

        return [
            'storyId' => $storyId,
            'storiesWithIdAndTitle' => yii::$app->story->getStoriesWithIdAndTitle($userId),
        ];
    }

    public function actionCharacterAvatar()
    {
        $avatarSavePath = Yii::getAlias('@uploads') . '/';
    
        $uploadedFile = UploadedFile::getInstanceByName('file');
        if (!$uploadedFile) {
            throw new Exception('Uploaded file not found');
        }
        // 文件限制:
        // .png, .jpg, .jpeg
        // 3000KB
        $allowedExtensions = ['png', 'jpg', 'jpeg'];
        if (!in_array($uploadedFile->extension, $allowedExtensions)) {
            throw new Exception('File extension not allowed, only ' . implode(', ', $allowedExtensions));
        }
        if ($uploadedFile->size > 3000 * 1024) {
            throw new Exception('File size exceeds limit (3000KB)');
        }
    
        $newFileName = uniqid() . '.' . $uploadedFile->extension;
        $savePath = $avatarSavePath . '/' . $newFileName;
    
        if (!$uploadedFile->saveAs($savePath)) {
            throw new Exception('File save failed');
        }
        
        return [
            'avatarFileName' => $newFileName,
        ];
    }    
}