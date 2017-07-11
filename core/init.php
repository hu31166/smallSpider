<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');
ini_set("display_errors", "no");
error_reporting(E_ALL);
date_default_timezone_set('PRC');
define('APP_PATH', str_replace('core'.DIRECTORY_SEPARATOR , '', dirname(__FILE__).DIRECTORY_SEPARATOR ));
include_once APP_PATH.'exceptions/Handler.php';
register_shutdown_function('exceptions\Handler::fatalError');
set_error_handler('exceptions\Handler::error');
set_exception_handler('exceptions\Handler::exceptionError');

include_once APP_PATH . 'config/config.php';
include_once APP_PATH.'core/function.php';
