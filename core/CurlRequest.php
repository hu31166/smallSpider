<?php
/**
 * Created by PhpStorm.
 * User: huangyugui
 * Date: 2016/10/27
 * Time: 下午3:01
 */

namespace core;


class CurlRequest
{

    public $timeout = 30;

    public $header = [];

    public $userAgent = 'php curl';

    public $referer = '';

    public $cookie = [];

    public $content = '';

    public $curlInfo = '';

    public $url = '';

    public function curl($url, $query = '', $type = 'GET')
    {
        $ch	= curl_init();
        if ($type == 'GET' && $query) {
            $url  = $url.'?'.(is_array($query) ? http_build_query($query) : $query);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        if ($type == 'POST') {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($query) ? http_build_query($query) : $query);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_REFERER, $this->referer);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        $this->content = curl_exec($ch);
        $this->curlInfo = curl_getinfo($ch);
        // 全部转utf-8
        $encode = mb_detect_encoding($this->content, array('ASCII', 'GB2312', 'GBK', 'UTF-8', 'CP936'));
        $encode = !$encode ? 'GBK' : $encode;
        if ($encode != 'UTF-8') {
            $this->content = mb_convert_encoding($this->content, 'utf-8', $encode);
            $this->content = preg_replace("/<meta([^>]*)charset=([^>]*)>/is", '<meta charset="UTF-8">', $this->content);
        }
        curl_close($ch);
//        Log::infoLog($this->content);
        $this->url = $url;
        $this->setResponseCookie($this->content);
    }

    public function setResponseCookie($data)
    {
        preg_match_all('/Set\-Cookie: (.*)\r\n/', $data, $match);
        $cookies = empty(end($match)) ? [] : end($match);

        // 解析到Cookie
        if (!empty($cookies)) {
            $cookies = implode(";", $cookies);
            $cookies = explode(";", $cookies);
            foreach ($cookies as $value)
            {
                $cookie = explode("=", $value);
                if (count($cookie) < 2 || empty(current($cookie)) || in_array(strtolower(current($cookie)), array('path', 'domain', 'expires', 'max-age'))) {
                    continue;
                }
                $this->cookie[current($cookie)] = end($cookie);
            }
        }
    }

    /**
     * 设置头
     * @param array $headerArr
     */
    public function addHeader($headerArr = [])
    {
        foreach ($headerArr as $key => $value) {
            $this->header[] = $key.': '.$value;
        }
    }

    /**
     * 客户端代理标识
     * @param $userAgent
     */
    public function setUserAgent($userAgent = false)
    {


        $this->userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36';
        $this->userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:49.0) Gecko/20100101 Firefox/49.0';

        if ($userAgent) {
            $this->userAgent = $userAgent;
        }
    }

    /**
     * 来源地址
     * @param $referer
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;
    }


}