<?php

namespace app\models\story;

use yii\db\ActiveRecord;

class Character extends ActiveRecord
{

    public function attributeLabels()
    {
        return [
            'storyId' => '故事ID',
            'name' => '角色名称',
            'feature' => '角色特征',
            'avatar' => '头像',
        ];
    }

    public function rules()
    {
        return [
            [['storyId', 'name', 'feature'], 'required', 'message' => '{attribute}不能为空'],
            ['storyId', 'integer', 'message' => '{attribute}必须是整数'],
            ['name', 'string', 'max' => 20, 'tooLong' => '{attribute}最多可以包含20个字符'],
            ['feature', 'string', 'max' => 400, 'tooLong' => '{attribute}最多可以包含400个字符'],
            ['avatar', 'string', 'max' => 255, 'tooLong' => '{attribute}最多可以包含255个字符'],
            [['name', 'feature'], 'match', 'pattern' => '/(```|---)/', 'not' => true, 'message' => '{attribute}不能包含```或---'],
        ];
    }

    public static function tableName()
    {
        return 'story_character';
    }

    /**
     * 获取该用户的角色信息的prompt
     * @return String
     */
    public function getCharacterInfoPrompt() {
        $prompt = <<<prompt
姓名: {$this->name}
特征:
```
{$this->feature}
```
prompt;
        return $prompt;
    }
}