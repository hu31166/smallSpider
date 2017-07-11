<?php
$GLOBALS['config']['domain'] = 'api.xicidaili.com';
$ipUrl = 'http://api.xicidaili.com/free2016.txt';
$ips = file_get_contents($ipUrl);
$ips = explode("\n", $ips);
foreach ($ips as $key => $value) {
    $ip = \core\Db::table('ips')->find("SELECT * FROM ips WHERE ip ='$value'");
    if ($ip) {
        continue;
    }
    \core\Db::table('ips')->insert(['ip' => $value]);
}
$ips = \core\Db::table('ips')->findAll("SELECT * FROM ips");
foreach ($ips as $key => $value) {
    if (checkip($value['ip']) === false) {
        \core\Db::table('ips')->delete("ip = '$value[ip]'");
    }
}

function checkip($proxy) {
    $ch	= curl_init();
    curl_setopt($ch,CURLOPT_URL, 'www.baidu.com');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch,CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);
    if ($curlInfo['http_code'] == 0) {
        return false;
    } else {
        return true;
    }
}
die;



