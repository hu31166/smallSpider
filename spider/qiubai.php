<?php
$GLOBALS['config'] = [
    'domain' => 'www.qiushibaike.com',
    'url' => 'http://www.qiushibaike.com',
    'fork' => 1,
    'redis' => true,
    'clear' => true,
    'usleep' => 0,
    'match_html' => [
        [
            'table' => 'qiubai',
            'url' => 'href=\"(\/article\/\d+)\"',
            'match' => [
                    [
                        'name' => 'content',
                        'xpath' => '/html/body/div[2]/div/div[1]/div[3]/div[2]/div[1]',
                        'type' => 'insert',
                    ],[
                        'name' => 'user',
                        'xpath' => '/html/body/div[2]/div/div[1]/div[3]/div[1]/a[2]/h2',
                        'type' => 'insert',
                    ]
                ]
        ],[
            'url' => 'href=\"(\/8hr\/page\/\d+\?s=\d+)\"',
        ]
    ]
];


