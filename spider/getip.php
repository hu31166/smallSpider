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

function checkip() {

}
die;



