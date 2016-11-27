<?php
namespace exceptions;
use core\Log;

class Handler extends \Exception
{
    public static function fatalError()
    {
        if ($e = error_get_last()) {
            $string = $e['message'].'. file '. $e['file'].'. line '. $e['line'];
            Log::errorLog($string);
        }
    }

    public static function error($errno, $errstr, $errfile, $errline)
    {
        $string = $errno.'. message '.$errstr.'. file '. $errfile.'. line '. $errline;
        Log::errorLog($string);
    }

    /**
     *
     * @param $e \Exception
     */
    public static function exceptionError(\Exception $e)
    {
        $string = $e->getMessage().'. file '. $e->getFile().'. line '.$e->getLine() ;
        Log::errorLog($string);
    }
}