<?php
/**
 * Created by PhpStorm.
 * User: huangyugui
 * Date: 2016/10/28
 * Time: 下午3:32
 */

namespace core;


use exceptions\SpiderException;

class Spider
{

    // 队列数组
    public $queue = [];

    // 处理的队列数组
    public $doneQueue = [];

    public $content = '';

    // 失败次数
    public $failNum = 0;

    // 成功次数
    public $successNum = 0;

    // 发现页面总数
    public $total = 0;

    // 内存
    public $memory = 0;

    // 开始时间
    public $beginTime = 0;

    // 内容日志
    public static $infoLog = [];

    // 错误日志
    public static $errorLog = '';

    public $queueUrl = '';

    // 需要启动进程的总数
    public $fork = '';

    public $forkId = 1;

    public $thisForkId = 1;

    public $redis = false;

    public $redisPrefix = '';

    public $clear = false;

    public $usleep = 0;

    public $domain = '';

    public $url = '';

    public $showLog = '';

    public $proxy = '';

    public $matchHtml = [];

    public $callback = [];

    /**
     * @var CurlRequest
     */
    public $curl = '';


    public function start()
    {
        $this->command();
        isset($GLOBALS['config']['domain']) && $this->redisPrefix = $GLOBALS['config']['domain'].'-';
        $this->beginTime = time();
        isset($GLOBALS['config']['redis']) && $this->redis = $GLOBALS['config']['redis'];
        isset($GLOBALS['config']['fork']) && $this->fork = $GLOBALS['config']['fork'];
        isset($GLOBALS['config']['clear']) && $this->clear = $GLOBALS['config']['clear'];
        isset($GLOBALS['config']['usleep']) && $this->usleep = $GLOBALS['config']['usleep'];
        isset($GLOBALS['config']['domain']) && $this->domain = $GLOBALS['config']['domain'];
        isset($GLOBALS['config']['url']) && $this->url = $GLOBALS['config']['url'];
        isset($GLOBALS['config']['show_log']) && $this->showLog = $GLOBALS['config']['show_log'];
        isset($GLOBALS['config']['proxy']) && $this->proxy = $GLOBALS['config']['proxy'];
        isset($GLOBALS['config']['match_html']) && $this->matchHtml = $GLOBALS['config']['match_html'];
        isset($GLOBALS['config']['callback']) && $this->callback = @$GLOBALS['config']['callback'];
        if ($this->redis == true) {
            Lredis::getInstance();
        }
        if ($this->fork > 1) {
            if ($this->redis == false) {
                throw SpiderException::err('多线程需要开启redis');
            }
            if (!function_exists('pcntl_fork')) {
                throw SpiderException::err('多线程需要开启pcntl_fork');
            }
        }
        // 清除上次的数据
        if ($this->clear == true) {
            $this->clear();
        }

        // 设置开始时间
        $this->curl = $curl = new CurlRequest();
        $curl->setUserAgent();
        $curl->setReferer($this->url);
        if ($this->proxy == true) {
            $curl->setProxy();
        }
        $this->addUrlQueue($this->url);
        $this->spiderBegin($curl);

    }

    /**
     * 清除数据
     */
    public function clear()
    {
        if ($this->redis == true) {
            // 清除redis缓存
            $keys = Lredis::getInstance()->keys($this->redisPrefix."*");
            foreach ($keys as $key => $value) {
                Lredis::getInstance()->del($value);
            }
        }
        // 清除日志
        if (file_exists(APP_PATH.'log'.DIRECTORY_SEPARATOR.Log::$file.'.log')) {
            @unlink(APP_PATH.'log'.DIRECTORY_SEPARATOR.Log::$file.'.log');
        }

    }

    /**
     * 设置任务状态
     */
    public function setStatus()
    {
        if ($this->redis == true) {
            Lredis::getInstance()->set($this->redisPrefix . 'failNum' . $this->thisForkId, $this->failNum);
            Lredis::getInstance()->set($this->redisPrefix . 'successNum' . $this->thisForkId, $this->successNum);
            Lredis::getInstance()->set($this->redisPrefix . 'memory' . $this->thisForkId, $this->memory);
            Lredis::getInstance()->set($this->redisPrefix . 'beginTime' . $this->thisForkId, $this->beginTime);
        }
    }
    /**
     * 命令参数
     */
    public function command()
    {
        $argv = $GLOBALS['argv'];
        $file = isset($argv[1]) ? $argv[1] : '';
        Log::$file = $file;
    }

    /**
     * 蜘蛛爬起来了
     * @param $url
     * @param CurlRequest $curl
     */
    public function spiderBegin(CurlRequest $curl)
    {
        Log::infoLog('队列数量：'.$this->getUrlQueueNum());
        $this->failNum = 0;
        $this->successNum = 0;
        $url = $this->getUrlQueueOne();
        $this->getHtml($url, $curl);
        Log::infoLog('队列数量：'.$this->getUrlQueueNum());
        while ($this->getUrlQueueNum() > 0) {
            usleep($this->usleep);
            $url = $this->getUrlQueueOne();
            if (strpos($url, $this->domain) === false) {
                $url = $this->domain.$url;
            }
            // 保证进程有工作做
            if ($this->redis == true) {
                if (Lredis::getInstance()->lLen($this->redisPrefix.'queue') > $this->fork * ($this->forkId + 1) && $this->fork > $this->forkId) {
                    $this->forkId++;
                    // 启动子进程
                    $this->startPcntlFork($curl);
                }
            }

            $this->getHtml($url, $curl);
            $this->setStatus();
            $this->panel();
            Log::infoLog('队列数量：'.$this->getUrlQueueNum());
        }
        die('进程结束');
    }

    public function startPcntlFork(CurlRequest $curl)
    {
        $pid = pcntl_fork();

        Lredis::getInstance()->close();
        Db::closeDb();

        if ($pid) {

            $this->beginTime = time();
            $this->thisForkId = $this->forkId;
            $this->failNum = 0;
            $this->successNum = 0;
            while ($this->getUrlQueueNum() > $this->fork * $this->thisForkId) {
                usleep($this->usleep);
                $url = $this->getUrlQueueOne();
                if (strpos($url, $this->domain) === false) {
                    $url = $this->domain . $url;
                }
                $this->getHtml($url, $curl);
                $this->setStatus();
            }

        } else {
//            Log::infoLog('启动进程失败');
        }
    }

    /**
     * 获取页面内容
     * @param $url
     * @param CurlRequest $curl
     * @return bool
     */
    public function getHtml($url, CurlRequest $curl)
    {
        $curl->curl($url);
        $httpCode = $curl->curlInfo['http_code'];

        if (!in_array($httpCode, [200, 301, 302])) {
            Log::infoLog($url.' 请求失败, http_code : '.$httpCode);
        }
        if (in_array($httpCode, ['0', '503', '502'])) {
            $this->addUrlQueue($this->queueUrl);
            $this->failNum++;
            return false;

        } elseif (in_array($httpCode, ['404'])) {

            $this->failNum++;
            $this->addDoneQueue();
            return false;

        } elseif (in_array($httpCode, ['301', '302'])) {

            $curl->curl($curl->curlInfo['redirect_url']);

        }

        $this->addDoneQueue();
        $this->getHtmlUrl($curl->content);
        $this->getHtmlFields($curl->content, $url);
        $this->successNum++;
        $this->memory = memory_get_usage();
        return true;
    }

    public function panel()
    {
        array_map(create_function('$a', 'print chr($a);'), array(27, 91, 72, 27, 91, 50, 74));

        $str = '';
        $str .= 'php version: '. PHP_VERSION."\r\n";
        $str .= 'target stie: '. $this->domain."\r\n";
        $str .= 'begin time : '. date("Y-m-d H:i:s", $this->beginTime).str_pad('',6);
        $str .= 'run time : '. sprintf("%.3f", (time() - $this->beginTime) / 3600) ." hours\r\n";
        $str .= 'ips : '.count($this->curl->ips)."\r\n";
        $str .= str_pad("", 70, "-")."\r\n";
        $str .= "success".str_pad("", 20-strlen("success"));
        $str .= "failed".str_pad("", 20-strlen("failed"));
        $str .= "mem".str_pad("", 20-strlen("mem"));
        $str .= "speed".str_pad("", 20-strlen("speed"));
        $str .= "\r\n";
        for ($i = 1; $i <= $this->forkId; $i++) {
            if ($this->redis == true) {
                $failNum = Lredis::getInstance()->get($this->redisPrefix.'failNum'.$i);
                $successNum = Lredis::getInstance()->get($this->redisPrefix.'successNum'.$i);
                $memory = Lredis::getInstance()->get($this->redisPrefix.'memory'.$i);
                $beginTime = Lredis::getInstance()->get($this->redisPrefix.'beginTime'.$i);
            } else {
                $failNum = $this->failNum;
                $successNum = $this->successNum;
                $memory = $this->memory;
                $beginTime = $this->beginTime;
            }


            $str .= $successNum.str_pad('', 20-strlen($successNum));
            $str .= $failNum.str_pad('', 20-strlen($failNum));
            $memory = sprintf("%.2f", $memory / 1000000);
            $str .= $memory.'m'.str_pad('', 20-strlen($memory));
            $time = time() - $beginTime;
            $speed = sprintf("%.2f", $time ? $successNum / $time : $time);
            $str .= $speed.'/s'.str_pad('', 20-strlen($speed));
            $str .= "\r\n";

        }
        $str .= str_pad("", 70, "-")."\r\n";
        $str .= "total".str_pad("", 20-strlen("total"));
        $str .= "queue".str_pad("", 20-strlen("queue"));
        $str .= "\r\n";
        $str .= $this->total.str_pad("", 20-strlen($this->total));
        
        if ($this->redis == true) {
            $queue = Lredis::getInstance()->lLen($this->redisPrefix . 'queue');
            $str .= $queue.str_pad("", 20-strlen($queue));
            
        } else {
            $str .= count($this->queue).str_pad("", 20-strlen(count($this->queue)));
            
        }
        $str .= "\r\n";
        if ($this->showLog == true) {
            foreach (self::$infoLog as $key => $value) {
                $str .= $value;
            }
        }
        echo $str;
    }

    /**
     * 爬取字段
     * @param $content
     * @param $url
     * @return bool
     */
    public function getHtmlFields($content, $url)
    {
        print_r($url);
        $url = str_replace($this->domain, '', $url);
        $match = '';

        foreach ($this->matchHtml as $key => $value) {

            if (preg_match('/\((.*)\)/', $value['url'], $pattern)) {
                $result = preg_match("/$pattern[0]/", $url);
            } else {
                $result = preg_match("/$value[url]/", $url);
            }

            if ($result) {
                $table = isset($value['table']) ? $value['table'] : '';
                if (isset($this->matchHtml[$key]['match'])) {
                    $match = $this->matchHtml[$key]['match'];
                }
                break;
            }
        }


        if (!$match) {

            return false;
        }
        libxml_use_internal_errors(true);
        $encode = get_encode($content);
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">'.$content);
        $xpath = new \DOMXPath($doc);
        $data = [];
        foreach ($match as $key => $value) {
            if (isset($value['value'])) {
                $data['category'] = $value['value'];
                continue;
            }
            $elements = $xpath->query($value['xpath']);

            if (!is_null($elements)) {
                foreach ($elements as $element) {
//                    $data[$value['name']] = $doc->saveXml($element);
                    $data[$value['name']] = $element->nodeValue;
                    if (isset($this->callback['fields'])) {
                        $data[$value['name']] = $this->callback['fields']($value['name'], $data[$value['name']], $encode);
                    }
                }
            }
        }
            if (!empty($data)) {
            $data['url'] = $this->domain.$url;
            if (isset($table)) {
                Db::table($table)->insert($data);
            }
        }

        return true;
    }

    /**
     * 获取html中的链接
     * @param $content
     */
    public function getHtmlUrl($content)
    {
        foreach ($this->matchHtml as $key => $value) {
            preg_match_all("/$value[url]/", $content, $match);
//            print_r(urldecode($match[0][0]));die;
            $match = array_filter($match);
            if ($match) {
                $urls = $match[0];
                $urls = array_unique($urls);
                $this->addUrlQueue($urls);
            }
        }
    }

    /**
     * url加入队列
     * @param $url
     */
    public function addUrlQueue($url)
    {
        if (!is_array($url)) {
            $url = [$url];
        }
        if ($this->redis == true) {
            foreach ($url as $key => $value) {
                if (!Lredis::getInstance()->exists($this->redisPrefix.md5($value))) {

                    Lredis::getInstance()->rPush($this->redisPrefix.'queue', $value);
                    Lredis::getInstance()->set($this->redisPrefix.md5($value), $value);

                }
            }
            $this->total = Lredis::getInstance()->lLen($this->redisPrefix . 'queue') + Lredis::getInstance()->lLen($this->redisPrefix . 'doneQueue');
        } else {
            foreach ($url as $key => $value) {
                if (array_search($value, $this->queue) === false
                    && array_search($value, $this->doneQueue) === false) {

                    array_push($this->queue, $value);

                }
            }
            $this->total = count($this->queue) + count($this->doneQueue);
        }

    }

    /**
     * 单条出队列
     * @return mixed
     */
    public function getUrlQueueOne()
    {
        if ($this->redis == true) {
            $url = Lredis::getInstance()->lPop($this->redisPrefix . 'queue');
            $this->queueUrl = $url;
        } else {
            $url = array_shift($this->queue);
            $this->queueUrl = $url;
        }
        foreach ($this->matchHtml as $key => $value) {
            if (preg_match("/$value[url]/", $url, $pattern)) {
                $url = end($pattern);
                $this->queueUrl = current($pattern);
                break;
            }
        }
        return $url;
    }

    /**
     * 队列总数
     * @return int
     */
    public function getUrlQueueNum()
    {
        if ($this->redis == true) {
            return Lredis::getInstance()->lLen($this->redisPrefix . 'queue');
        } else {
            return count($this->queue);
        }
    }

    /**
     * 完成的队列
     */
    public function addDoneQueue()
    {
        if ($this->redis == true) {
            Lredis::getInstance()->rPush($this->redisPrefix.'doneQueue', $this->queueUrl);
        } else {
            array_push($this->doneQueue, $this->queueUrl);
        }

    }
}