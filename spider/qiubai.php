<?php
$GLOBALS['config'] = [
    'domain' => 'www.qiushibaike.com', // 域名
    'url' => 'www.qiushibaike.com', // 入口地址
    'fork' => 4, // 进程数量
    'redis' => true, // 是否启用redis
    'clear' => true, // 开启redis情况下, 是否清除上次数据
    'usleep' => 600000, // 睡眠时间, 单位微秒
    'show_log' => true, // 显示日志, 终端显示最新10条
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
    ],
    'callback' => [
        'fields' => function($key, $value) {
            // 入库处理字段数据回调方法
            $encode = get_encode($value);
            if ($encode == 'CP936' && !empty($value)) {
                if (iconv('utf-8', 'latin1//IGNORE', $value)) {
                    $value = iconv('utf-8', "latin1//IGNORE", $value);
                }
            }
            return $value;
        }
    ]
];




