<?php
namespace core;


class Lredis
{
    /**
     * @var \Redis
     */
    public $redis; 
    
    private static $_instance = null;
    
    function __construct(){
        $this->connect();
    }

    public function connect()
    {
        $this->redis = new \redis();
        $this->redis->connect(REDIS_HOST, REDIS_PORT);
    }

    public function close()
    {
        $this->redis->close();
        $this->redis = null;
        self::$_instance = null;
    }
    
    /**
     * 单例模式
     * @return Lredis
     */
    public static function getInstance()
    {
        if(self::$_instance == null) return self::$_instance = new self();
        else return self::$_instance;
    }

    /**
     * 写入缓存
     * @param $key
     * @param $value
     * @return bool
     */
    public function set($key, $value){
        if(is_array($value)){
            return $this->set_arr($key, serialize($value));
        }else{
            return $this->redis->set($key,$value);
        }
    }


    /**
     * 缓存数组
     * @param $key
     * @param $value
     * @return bool
     */
    public function set_arr($key,$value){
        return $this->redis->set($key, serialize($value));
    }
    
    /**
     * 读取缓存
     * @param string $key
     * @return string
     */
    public function get($key){
        $value = $this->redis->get($key);
        if(is_serialized($value)){
            return unserialize($value);
        }
        return $value;
    }
    
    /**
     * 读取序列化缓存
     * @return array
     */
    public function get_arr($key){
       return unserialize($this->redis->get($key));
    }
    
    /**
     * 删除缓存
     * @param string $key
     */
    public function del($key)
    {
        $this->redis->delete($key);
    }

    /**
     * @param $name
     * @return array
     */
    public function keys($name)
    {
        return $this->redis->keys($name);
    }
    
    /**
     * 不存在则设置缓存
     * @param string $key
     * @param string|array $value
     */
    public function setnx($key,$value)
    {
        if(is_array($value)){
            $value = serialize($value);
        }
        $this->redis->setnx($key,$value);
    }

    /**
     * 指定的键是否存在
     * @param $key
     * @return bool
     */
    public function exists($value)
    {
       return $this->redis->exists($value);
    }

    /**
     * 值递增
     * @param $key
     * @return int
     */
    public function incr($key)
    {
        return $this->redis->incr($key);
    }

    /**
     * 值递减
     * @param $key
     * @return int
     */
    public function decr($key)
    {
        return $this->redis->decr($key);
    }
    
    
    /**
     * @param array $par
     * @return array
     */
    public function getMultiple($par){
        $arr = $this->redis->getMultiple($par);
        foreach($arr as $k => $v){
            if(is_serialized( $v)){
                $arr[$k] = unserialize($v);
            }
        }
        return $arr;
    }

    /**
     * 表头增加值
     * @param $key
     * @param $value
     * @return int
     */
    public function lPush($key,$value){
        if(is_array($value)){
            $value = serialize($value);
        }
        return $this->redis->lPush($key,$value);
    }

    /**
     * 表尾增加值
     * @param $key
     * @param $value
     * @return int
     */
    public function rPush($key,$value){
        if(is_array($value)){
            $value = serialize($value);
        }
        return $this->redis->rPush($key,$value);
    }


    /**
     * 删除表第一个元素
     * @param $key
     * @return mixed|string
     */
    public function lPop($key){
        $value = $this->redis->lPop($key);
        if(is_serialized($value)){
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * 返回列表长度
     * @param $key
     * @return int
     */
    public function lLen($key){
        return $this->redis->lLen($key);
    }

    /**
     * 返回指定表第N个元素
     * @param $key
     * @param $number
     * @return mixed|String
     */
    public function lget($key,$number){
        $value = $this->redis->lIndex($key,$number);
        if(is_serialized($value)){
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * 设置指定表索引值
     * @param $key
     * @param $number
     * @param $value
     * @return bool
     */
    public function lset($key,$number,$value){
        if(is_array($value)){
            $value = serialize($value);
        }
        return $this->redis->lSet($key,$number,$value);
    }

    /**
     * 表开始到结束的值
     * @param $key
     * @param $start
     * @param $end
     * @return array
     */
    public function lgetrange($key,$start,$end){
        $arr = $this->redis->lRange($key,$start,$end);
        foreach($arr as $k => $v){
            if(is_serialized( $v)){
                $arr[$k] = unserialize($v);
            }
        }
        return $arr;
    }

    /**
     * 匹配值并删除
     * @param $key
     * @param $value
     * @param int $number
     * @return int
     */
    public function lremove($key,$value,$number=1){
        if(is_array($value)){
            $value = serialize($value);
        }
        return $this->redis->lRem($key,$value,$number);
    }

    /**
     * 增加值，存在返回false
     * @param $key
     * @param $value
     * @return int
     */
    public function sadd($key,$value){
        if(is_array($value)){
            $value = serialize($value);
        }
        return $this->redis->sAdd($key,$value);
    }

    /**
     * 删除表指定值
     * @param $key
     * @param $value
     */
    public function sremove($key,$value){
        if(is_array($value)){
            $value = serialize($value);
        }
        $this->redis->sRemove($key,$value);
    }

    /**
     * 表1的值移动到表2
     * @param $key_one
     * @param $key_two
     * @param $value
     * @return bool
     */
    public function smove($key_one,$key_two,$value){
        if(is_array($value)){
            $value = serialize($value);
        }
        return $this->redis->sMove($key_one,$key_two,$value);
    }

    /**
     * 检查key表中是否存在该值
     * @param $key
     * @param $value
     * @return bool
     */
    public function sIsMember($key,$value){
        if(is_array($value)){
            $value = serialize($value);
        }
        return $this->redis->sIsMember($key,$value);
    }

    /**
     * 返回表个数
     * @param $key
     * @return array|bool
     */
    public function sCard($key){
        return $this->redis->sCard($key);
    }

    /**
     * 随机删除值
     * @param $key
     * @return mixed|string
     */
    public function sPop($key){
        $value = $this->redis->sPop($key);
        if(is_serialized($value)){
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * 返回表内容
     * @param string $key
     * @return mixed
     */
    public function smembers($key){
        $arr = $this->redis->sMembers($key);
        foreach ($arr as $k => $v){
            if(is_serialized( $v)){
                $arr[$k] = unserialize($v);
            }
        }
        return $arr;
    }
}

?>