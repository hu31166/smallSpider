<?php
$GLOBALS['config']['domain'] = 'zhaopin';
libxml_use_internal_errors(true);

$curl = new \core\CurlRequest();
$curl->setUserAgent();

$searchArr = ['php', 'java', 'ios', 'android', '.net', '前端', '产品经理'];

foreach ($searchArr as $value) {
    $value = urlencode($value);
    $urlArr = [
        [
            'url' => 'http://sou.zhaopin.com/jobs/searchresult.ashx?jl=%E5%B9%BF%E5%B7%9E&kw='.$value.'&p=1&isadv=0',
            'xpath' => '/html/body/div[3]/div[3]/div[2]/span[1]/em',
            'name' => 'zhilian',
        ], [
            'url' => 'http://search.51job.com/jobsearch/search_result.php?fromJs=1&jobarea=030200&funtype=0000&industrytype=00&keyword='.$value.'&keywordtype=2&lang=c&stype=2&postchannel=0000&fromType=1&confirmdate=9',
            'xpath' => '/html/body/div[2]/div[5]/div[1]/div[3]',
            'name' => '51',
        ],
    ];
    foreach ($urlArr as $k => $v) {

        $query = parse_url($v['url']);
        $curl->curl($query['host'].$query['path'], $query['query']);

        $encode = get_encode($curl->content);
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">'.$curl->content);
        $xpath = new \DOMXPath($doc);
        $elements = $xpath->query($v['xpath']);
        $number = 0;
        if (!is_null($elements)) {
            foreach ($elements as $element) {
                $number = $element->nodeValue;
            }
        }
        $number = preg_match('/\d+/', $number, $match);
        $data = [
            'search' => urldecode($value),
            'number' => $match[0],
            'site_name' => $v['name'],
            'time' => time(),
            'url' => $v['url'],
        ];

        \core\Db::table('zhaopin')->insert($data);
    }
}

die;
