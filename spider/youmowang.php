<?php
$GLOBALS['config'] = [
    'domain' => 'www.haha365.com',
    'url' => 'http://www.haha365.com/xd_joke/676404.htm',
    'fork' => 8,
    'redis' => true,
    'clear' => true,
    'usleep' => 0,
    'show_log' => true,
    'match_html' => [
        [
            'table' => 'youmowang',
            'url' => 'href=\"(\/[a-z]+_joke\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '/html/body/div[3]/div/div[3]/div[2]/div/div[1]/h1',
                ],[
                    'name' => 'content',
                    'xpath' => '/html/body/div[3]/div/div[3]/div[2]/div/div[3]',
                ]
            ]
        ],[
            'url' => 'href=\"(\/joke\/index_\d+.htm)\"',
        ]
    ]
];

