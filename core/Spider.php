<?php
/**
 * Created by PhpStorm.
 * User: huangyugui
 * Date: 2016/10/28
 * Time: 下午3:32
 */

namespace core;


class Spider
{

    // 队列数组
    public static $queue = [];

    public $content = '';

    // 失败次数
    public static $failNum = 0;

    // 成功次数
    public static $successNum = 0;

    // 发现页面总数
    public static $total = 0;

    // 内存
    public static $memory = 0;

    public function start()
    {
        $this->command();
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
        $this->getHtml($url, $curl);
        while ($this->getUrlQueueNum() > 0) {
//            sleep(1);
            $url = $this->getUrlQueueOne();
            if (strpos($url, $GLOBALS['config']['domain']) === false) {
                $url = $GLOBALS['config']['url'].$url;;
            }
            $this->getHtml($url, $curl);
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
            echo '请求失败, http_code'.$httpCode.' 请求频率应该过高';
            $this->addUrlQueue($url);
            return false;
        } elseif (in_array($httpCode, ['404'])) {
            echo '请求失败, http_code'.$httpCode;
            self::$failNum++;
            return false;
        } elseif (in_array($httpCode, ['301', '302'])) {
            $curl->curl($curl->curlInfo['redirect_url']);
        }
        $this->getHtmlUrl($curl->content);
        $this->getHtmlFields($curl->content, $url);
        self::$successNum++;
        array_map(create_function('$a', 'print chr($a);'), array(27, 91, 72, 27, 91, 50, 74));
        echo self::$total."\r\n";
        echo self::$successNum."\r\n";
        echo self::$failNum."\r\n";
        echo (memory_get_usage() / 1000000)."m \r\n";
        return true;
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
                if (isset($GLOBALS['config']['match_html'][$key]['match'])) {
                    $match = $GLOBALS['config']['match_html'][$key]['match'];
                }
                break;
            }
        }
        if (!$match) {
            return false;
        }
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
        $data['url'] = $GLOBALS['config']['url'].$url;
        if (!$data['content']) {
            $data['html'] = serialize($content);
        }
        Db::table('qiubai')->insert($data);
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
            self::$queue = array_merge(self::$queue, $url);
        } else {
            array_push(self::$queue, $url);
        }
        self::$queue = array_unique(self::$queue);
        self::$total = count(self::$queue) + self::$successNum + self::$failNum;
    }

    /**
     * 单条出队列
     * @return mixed
     */
    public function getUrlQueueOne()
    {
        $url = current(self::$queue);
        $num = array_search($url, self::$queue);
        unset(self::$queue[$num]);
        foreach ($GLOBALS['config']['match_html'] as $key => $value) {
            if (preg_match("/$value[url]/", $url, $pattern)) {
                $url = end($pattern);
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
        return count(self::$queue);
    }
}