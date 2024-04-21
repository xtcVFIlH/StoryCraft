<?php

namespace errors;

use \Exception;

/**
 * ai生成的内容格式不符合要求
 */
class GeneratedContentFormatException extends Exception
{
    public function __construct($message = 'Generated content format error', $code = 224)
    {
        parent::__construct($message, $code);
    }
}