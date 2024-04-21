<?php

namespace app\models;

use yii\db\ActiveRecord;
use \Exception;

class Story extends ActiveRecord
{

    public function attributeLabels()
    {
        return [
            'title' => '标题',
            'backgroundInfo' => '背景信息',
        ];
    }
    
    public function rules()
    {
        return [
            [['title', 'backgroundInfo'], 'required', 'message' => '{attribute}不能为空'],
            ['title', 'string', 'max' => 20, 'tooLong' => '标题最多可以包含20个字符'],
            ['backgroundInfo', 'string', 'max' => 900, 'tooLong' => '背景信息最多可以包含900个字符'],
            [['backgroundInfo'], 'match', 'pattern' => '/(```|---)/', 'not' => true, 'message' => '{attribute}不能包含```或---'],
        ];
    }

    public static function tableName()
    {
        return 'story';
    }

    public function getCharacters()
    {
        return $this->hasMany(story\Character::className(), ['storyId' => 'id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->createAt = time();
            }
            return true;
        }
        return false;
    }

    /**
     * 获取该故事信息的prompt
     * 包含故事背景prompt和角色信息prompt
     * @return String
     */
    public function getStoryInfoPrompt()
    {
        $characters = $this->characters;
        if (!$characters) {
            throw new \Exception('characters not found', 10014);
        }
        $charactersPrompt = '';
        foreach ($characters as $character) {
            if ($charactersPrompt != '') {
                $charactersPrompt .= "\n\n";
            }
            $charactersPrompt .= $character->getCharacterInfoPrompt();
        }
        $prompt = <<<prompt
故事背景:
---

{$this->backgroundInfo}

---

角色信息:
---

{$charactersPrompt}

---
prompt;
        return $prompt;
    }
}