<?php


$GLOBALS['config'] = [
    'domain' => 'www.goxav.info/', // 域名
    'url' => 'http://www.goxav.info/forum.php?mod=viewthread&amp;tid=1129084&amp;extra=page%3D1', // 入口地址
    'fork' => 1, // 进程数量
    'redis' => redis, // 是否启用redis
    'clear' => true, // 开启redis情况下, 是否清除上次数据
    'usleep' => 6000000, // 睡眠时间, 单位微秒
    'show_log' => true, // 显示日志, 终端显示最新10条
    //href="forum.php?mod=viewthread&tid=1127892&extra=page%3D1"
    'match_html' => [
        [
            'url' => 'href=\"(forum.php\?mod=viewthread&amp;tid=\d+&amp;extra=page%3D1)\"',
            'table' => 'xiaav',
            'match' => [
                [
                    'name' => 'title',
                    'xpath' => '//*[@id="thread_subject"]',
                ],[
                    'name' => 'content',
                    'xpath' => '//*[@class="t_f"]',
                ]
            ]
        ],[
            'url' => 'href=\"(forum.php?mod=forumdisplay&fid=151&page=\d+)\"',
        ]
    ]
];




