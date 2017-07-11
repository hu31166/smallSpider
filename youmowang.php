<?php
include_once 'core/init.php';
$spider = new \core\Spider();


$GLOBALS['config'] = [
    'domain' => 'www.haha365.com',
    'url' => 'http://www.haha365.com/joke/',
    'fork' => 1,
    'redis' => false,
    'clear' => true,
    'usleep' => 0,
    'show_log' => true,
    'proxy' => false, // 是否启用IP代理
    'match_html' => [
        [
            'url' => 'href=\"(\/joke\/)\"',
        ],[
            'url' => 'href=\"(\/bxww\/)\"',
        ],[
            'url' => 'href=\"(\/hahags\/)\"',
        ],[
            'url' => 'href=\"(\/joke\/index_\d+.htm)\"',
        ], [
            'url' => 'href=\"(\/bxww\/index_\d+.htm)\"',
        ], [
            'url' => 'href=\"(\/hahags\/index_\d+.htm)\"',
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/[a-z]+_joke\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/div[1]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '笑话',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/zldw\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '哲理短文',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/xxs\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '小小说',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/mjgs\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '民间故事',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/kbgs\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '恐怖故事',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/ymgs\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '幽默故事',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/yqww\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '谐趣网文',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/yqww\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '谐趣网文',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/bzzw\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '辛辣杂文',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/bxwl\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '报销网络',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/bxwl\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '报销网络',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/xygx\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '校园搞笑',
                ]
            ]
        ], [
            'table' => 'youmowang',
            'url' => 'href=\"(\/gwxb\/\d+.htm)\"',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="content"]/h1',
                ], [
                    'name' => 'content',
                    'xpath' => '//*[@id="endtext"]',
                ], [
                    'name' => 'category',
                    'value' => '古文笑编',
                ]
            ]
        ]
    ]
];

$spider->start();