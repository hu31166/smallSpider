<?php
namespace exceptions;
/**
 * Created by PhpStorm.
 * User: huangyugui
 * Date: 2016/10/25
 * Time: 下午5:01
 */
class SpiderException extends \InvalidArgumentException
{
    public static function err($message, $code = 400)
    {
        return new static($message, $code);
    }
}
