<?php
namespace core;
class Log {

    public static function errorLog($message)
    {
        $message = date('Y-m-d H:i:s', time())." : ".$message."\r\n";
        error_log($message, 3, $GLOBALS['config']['domain'].'.log');
        \core\Spider::$errorLog = $message;
    }

    public static function infoLog($message)
    {
        $message = date('Y-m-d H:i:s', time())." : ".$message."\r\n";
        error_log($message, 3, $GLOBALS['config']['domain'].'.log');
        \core\Spider::$infoLog[] = $message;
    }

}