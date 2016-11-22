<?php
namespace exceptions;
class Handler extends \Exception 
{
    public static function fatalError()
    {
        if ($e = error_get_last()) {
            $string = $e['message'].'. file '. $e['file'].'. line '. $e['line'];
            dump($string);
        }
    }

    public static function error($errno, $errstr, $errfile, $errline)
    {
        $string = $errno.'. message '.$errstr.'. file '. $errfile.'. line '. $errline;
        dump($string);
    }

    /**
     *
     * @param $e \Exception
     */
    public static function exceptionError(\Exception $e)
    {
        $string = $e->getMessage().'. file '. $e->getFile().'. line '.$e->getLine() ;
        dump($string);
    }
}