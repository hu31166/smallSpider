<?php

function dump($data)
{
    print_r($data);
}

function vdump($data)
{
    var_dump($data);
}


/**
 * @param $class 自动加载
 */
spl_autoload_register(function($class){
    $class = str_replace('\\', '/', $class);
    if(file_exists(APP_PATH.$class.'.php')){
        include APP_PATH.$class.'.php';
    }
});




