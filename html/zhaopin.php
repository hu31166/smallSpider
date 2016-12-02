<?php
$path = dirname(__FILE__).'/';
define('APP_PATH', dirname(dirname(__FILE__)).'/');
include_once '../core/init.php';
$GLOBALS['config']['domain'] = 'zhaopinhtml';

$searchArr = ['java', '产品经理', '前端', '.net', 'php', 'android', 'ios'];
$siteName = $_GET['type'];
$data = [];
foreach ($searchArr as $key => $value) {
    $sql = "SELECT * FROM zhaopin WHERE site_name = '$siteName' AND search = '$value'";
    $result = \core\Db::db()->findAll($sql);
    foreach ($result as $k => $v) {
        $data[$k]['time'] = date('Y-m-d', $v['time']);
        $data[$k][$value] = (int) $v['number'];
    }
}
$jsonData = ['data' => $data, 'ykeys' => $searchArr, 'labels' => $searchArr, 'lineColors' => ['#3c8dbc','#3c8dbc']];

echo json_encode($jsonData);

