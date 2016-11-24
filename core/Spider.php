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
    public $failNum = [];

    // 成功次数
    public $successNum = [];

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

    public function start()
    {
        $this->command();

        $this->redisPrefix = $GLOBALS['config']['domain'];
        $this->beginTime = time();
        $this->redis = $GLOBALS['config']['redis'];
        $this->fork = $GLOBALS['config']['fork'];
        if ($this->redis == true) {
            Lredis::getInstance();

            // 清除上次运行的数据
            Lredis::getInstance()->del($this->redisPrefix.'queue');
            Lredis::getInstance()->del($this->redisPrefix.'doneQueue');
            $keys = Lredis::getInstance()->keys($this->redisPrefix."*");
            foreach ($keys as $key => $value) {
                Lredis::getInstance()->del($value);
            }
        }
        if ($this->fork) {
            if ($this->redis == false) {
                throw SpiderException::err('多线程需要开启redis');
            }
            if (!function_exists('pcntl_fork')) {
                throw SpiderException::err('多线程需要开启pcntl_fork');
            }
        }
        $curl = new CurlRequest();
        $curl->setUserAgent();
        $curl->setReferer($GLOBALS['config']['url']);
        $url = $GLOBALS['config']['url'];
//        $url = 'http://www.qiushibaike.com/article/118022870';
        $this->spiderBegin($url, $curl);
    }

    /**
     * 命令参数
     */
    public function command()
    {
        $argv = $GLOBALS['argv'];
        $file = isset($argv[1]) ? $argv[1] : '';
        if (!$file) {
            echo 'Please enter the file operation, such as # php run <filename>';
        }
        if (!file_exists(APP_PATH.'/spider/'.$file.'.php')) {
            echo 'File not found, please make sure the '.$file.'.php exists spider folder';
        }
        include_once APP_PATH.'/spider/'.$file.'.php';

    }

    /**
     * 蜘蛛爬起来了
     * @param $url
     * @param CurlRequest $curl
     */
    public function spiderBegin($url, CurlRequest $curl)
    {
        $this->failNum = 0;
        $this->successNum = 0;
        $this->getHtml($url, $curl);
        while ($this->getUrlQueueNum() > 0) {
//            usleep(200000);
            $url = $this->getUrlQueueOne();
            if (strpos($url, $GLOBALS['config']['domain']) === false) {
                $url = $GLOBALS['config']['domain'].$url;
            }
            // 保证进程有工作做
            if ($this->queue > $this->fork * 2 && $this->fork > 0) {
                $this->fork--;
                $this->forkId++;
                $this->startPcntlFork($curl);
            }
            $this->getHtml($url, $curl);
            $this->panel();
        }
    }

    public function startPcntlFork(CurlRequest $curl)
    {
        $pid = pcntl_fork();
        if ($pid) {
            $this->thisForkId = $this->forkId;
            $this->failNum = 0;
            $this->successNum = 0;
            while ($this->getUrlQueueNum() > 0) {
                $url = $this->getUrlQueueOne();
                if (strpos($url, $GLOBALS['config']['domain']) === false) {
                    $url = $GLOBALS['config']['domain'].$url;
                }
                $this->getHtml($url, $curl);
            }
            die();
        } else {
//            Log::infoLog('启动进程失败'.$pid);
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
        if (in_array($httpCode, ['0', '503'])) {

            Log::infoLog($url.' 请求失败, http_code : '.$httpCode.' 请求频率应该过高');
            $this->addUrlQueue($this->queueUrl);
            $this->failNum++;
            return false;

        } elseif (in_array($httpCode, ['404'])) {

            Log::infoLog($url.' 请求失败, http_code : '.$httpCode);
            $this->failNum++;
            $this->addDoneQueue();
            return false;

        } elseif (in_array($httpCode, ['301', '302'])) {

            $curl->curl($curl->curlInfo['redirect_url']);

        }

        $this->addDoneQueue();
        $this->successNum++;
        $this->getHtmlUrl($curl->content);
        $this->getHtmlFields($curl->content, $url);
        return true;
    }

    public function panel()
    {
        array_map(create_function('$a', 'print chr($a);'), array(27, 91, 72, 27, 91, 50, 74));

        $str = '';
        $str .= 'php version: '. PHP_VERSION."\r\n";
        $str .= 'target stie: '. $GLOBALS['config']['domain']."\r\n";
        $str .= 'begin time : '. date("Y-m-d H:i:s", $this->beginTime).str_pad('',6);
        $str .= 'run time : '. sprintf("%.3f", (time() - $this->beginTime) / 3600) ." hours\r\n";
        $str .= str_pad("", 70, "-")."\r\n";
        $str .= "success".str_pad("", 20-strlen("success"));
        $str .= "failed".str_pad("", 20-strlen("failed"));
        $str .= "mem".str_pad("", 20-strlen("mem"));
        $str .= "speed".str_pad("", 20-strlen("speed"));
        $str .= "\r\n";
        $str .= $this->successNum.str_pad('', 20-strlen($this->successNum));
        $str .= $this->failNum.str_pad('', 20-strlen($this->failNum));
        $mem = sprintf("%.2f", memory_get_usage() / 1000000);
        $str .= $mem.'m'.str_pad('', 20-strlen($mem));
        $time = time() - $this->beginTime;
        $speed = sprintf("%.2f", $time ? $this->successNum / $time : $time);
        $str .= $speed.'/s'.str_pad('', 20-strlen($speed));
        $str .= "\r\n";
        $str .= str_pad("", 70, "-")."\r\n";
        $str .= "total".str_pad("", 20-strlen("total"));
        $str .= "queue".str_pad("", 20-strlen("queue"));
        $str .= "\r\n";
        $str .= $this->total.str_pad("", 20-strlen($this->total));
        if ($this->redis == true) {
            $str .= Lredis::getInstance()->lLen($this->redisPrefix . 'queue').str_pad("", 20-strlen(Lredis::getInstance()->lLen($this->redisPrefix . 'queue')));
        } else {
            $str .= count($this->queue).str_pad("", 20-strlen(count($this->queue)));
        }
        $str .= "\r\n";
//        foreach (self::$infoLog as $key => $value) {
//            $str .= $value;
//        }
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
        $url = str_replace($GLOBALS['config']['url'], '', $url);
        $match = '';
        foreach ($GLOBALS['config']['match_html'] as $key => $value) {
            if (preg_match('/\((.*)\)/', $value['url'], $pattern)) {
                $result = preg_match("/$pattern[0]/", $url);
            } else {
                $result = preg_match("/$value[url]/", $url);
            }
            if ($result) {
                $table = $value['table'];
                if (isset($GLOBALS['config']['match_html'][$key]['match'])) {
                    $match = $GLOBALS['config']['match_html'][$key]['match'];
                }
                break;
            }
        }
        if (!$match) {
            return false;
        }
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">'.$content);
        $xpath = new \DOMXPath($doc);
        $data = [];
        foreach ($match as $key => $value) {
            $elements = $xpath->query($value['xpath']);
            if (!is_null($elements)) {
                foreach ($elements as $element) {
                    $data[$value['name']] = $element->nodeValue;
                }
            }
        }
        $data['url'] = $url;
        if (isset($table)) {
            Db::table($table)->insert($data);
        }
    }

    /**
     * 获取html中的链接
     * @param $content
     */
    public function getHtmlUrl($content)
    {
        foreach ($GLOBALS['config']['match_html'] as $key => $value) {
            preg_match_all("/$value[url]/", $content, $match);
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
        if (is_array($url)) {
            if ($this->redis == true) {
                foreach ($url as $key => $value) {
                    if (!Lredis::getInstance()->exists($this->redisPrefix.md5($value))) {
                        Lredis::getInstance()->rPush($this->redisPrefix.'queue', $value);
                        Lredis::getInstance()->set($this->redisPrefix.md5($value), $value);
                    }
                }
            } else {
                foreach ($url as $key => $value) {
                    if (array_search($value, $this->queue) === false
                        && array_search($value, $this->doneQueue) === false) {
                        array_push($this->queue, $value);
                    }
                }
            }

        } else {
            if ($this->redis == true) {
                if (!Lredis::getInstance()->exists($this->redisPrefix.md5($url))) {
                    Lredis::getInstance()->rPush($this->redisPrefix.'queue', $url);
                    Lredis::getInstance()->set($this->redisPrefix.md5($url), $url);
                }
            } else {
                if (array_search($url, $this->queue) === false && array_search($url, $this->doneQueue) === false) {
                    array_push($this->queue, $url);
                }
            }
        }
        if ($this->redis == true) {
            $this->total = Lredis::getInstance()->lLen($this->redisPrefix . 'queue') + Lredis::getInstance()->lLen($this->redisPrefix . 'doneQueue');
        } else {
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
        foreach ($GLOBALS['config']['match_html'] as $key => $value) {
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