<?php

namespace app\dto\story;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use \Exception;

class StoryInfo implements \JsonSerializable
{
    /** @var String */
    protected $title;
    /** @var String */
    protected $backgroundInfo;
    /**
     * @var Array{
     *   name: String,
     *   feature: String,
     *   ...
     * }[]
     */
    protected $characterInfos;

    /**
     * @return Array{
     *   title: String,
     *   backgroundInfo: String,
     *   characterInfos: Array{
     *     name: String,
     *     feature: String,
     *     ...
     *   }[]
     * }
     */
    public function toArray()
    {
        return [
            'title' => $this->title,
            'backgroundInfo' => $this->backgroundInfo,
            'characterInfos' => $this->characterInfos,
        ];
    }

    /**
     * @return Array{
     *   title: String,
     *   backgroundInfo: String,
     *   characterInfos: Array{
     *     name: String,
     *     feature: String,
     *     ...
     *   }[]
     * }
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @param Array{
     *   title: String,
     *   backgroundInfo: String,
     *   characterInfos: Array<String, String>
     * } $data 用于初始化故事信息的数据
     * @throws Exception 当校验失败时抛出异常
     */
    function __construct($data)
    {
        $titleValidator = v::key('title', v::stringType()->notEmpty()->setName('故事标题')
            ->setTemplate('{{name}} 不能为空'));
    
        $backgroundInfoValidator = v::key('backgroundInfo', v::stringType()->notEmpty()->setName('故事背景')
            ->setTemplate('{{name}} 不能为空'));
    
        $characterInfosValidator = v::key('characterInfos', v::arrayType()->notEmpty()->setName('角色信息列表')
            ->setTemplate('{{name}} 不能为空'));
    
        $characterValidator = v::key('characterInfos', v::arrayType()->notEmpty()->setName('角色信息列表')
            ->each(
                v::key('name', v::stringType()->notEmpty()->setName('角色名')
                    ->setTemplate('{{name}} 不能为空'))
                ->key('feature', v::stringType()->notEmpty()->setName('角色特征')
                    ->setTemplate('{{name}} 不能为空'))
            ));

        try {
            $titleValidator->assert($data);
            $backgroundInfoValidator->assert($data);
            $characterInfosValidator->assert($data);
            $characterValidator->assert($data);
        } catch (NestedValidationException $error) {
            $messages = $error->getMessages();
            foreach ($messages as $message) {
                throw new Exception($message);
            }
        }

        $this->title = $data['title'];
        $this->backgroundInfo = $data['backgroundInfo'];
        $this->characterInfos = $data['characterInfos'];
    }
}