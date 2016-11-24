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

/**
 * 判断是否序列化
 * @param string $data
 * @return boolean
 */
function is_serialized( $data ) {
    $data = trim( $data );
    if ( 'N;' == $data )
        return true;
    if ( !preg_match( '/^([adObis]):/', $data, $badions ) )
        return false;
    switch ( $badions[1] ) {
        case 'a' :
        case 'O' :
        case 's' :
            if ( preg_match( "/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data ) )
                return true;
            break;
        case 'b' :
        case 'i' :
        case 'd' :
            if ( preg_match( "/^{$badions[1]}:[0-9.E-]+;\$/", $data ) )
                return true;
            break;
    }
    return false;
}




