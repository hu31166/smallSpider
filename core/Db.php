<?php
namespace core;
use exceptions\SpiderException;

/**
 * Created by PhpStorm.
 * User: huangyugui
 * Date: 2016/10/25
 * Time: 下午4:36
 */

class Db
{

    private static $type = 'mysql';

    /**
     * @var \PDO
     */
    private static $db = null;

    private static $_instance = null;

    public static $table = '';

    private $molecule = '';

    public function __construct() {
    }

    public static function connect() {
        if (self::$db == null) {
            self::$db = new \PDO(self::$type . ':host=' . DB_HOST . ';dbname=' . DB_DATABASE, DB_USERNAME, DB_PASSWORD);
            self::$db->query('set names utf8');
        }
    }

    public static function closeDb() {
        self::$db = null;
        self::$_instance = null;
    }

    /**
     * 单例模式
     * @return Db|null
     */
    private static function getInstance() {

        if(self::$_instance == null) return self::$_instance = new self();
        else return self::$_instance;
    }

    /**
     * @return Db|null
     */
    public static function db(){

        self::connect();
        return self::getInstance();
    }

    public static function table($tableName) {
        if (empty($tableName)) {
            SpiderException::err('table name is not empty');
        }
        self::connect();
        self::$table = $tableName;
        return self::getInstance();
    }

    /**
     * 插入数据
     * @param $data
     * @return mixed
     */
    public function insert($data) {
        $bind = [];
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $value = htmlspecialchars($value);
            $fields[] = "`$key`";
            $values[] = "?";
            $bind[] = $value;
        }
        $sql = "INSERT INTO ".self::$table.' ('.(implode(',', $fields)).') values('.(implode(',', $values)).')';
        $stmt =  self::$db->prepare($sql);
        $stmt->execute($bind);
        $this->error($stmt, $sql);
        return self::$db->lastInsertId();
    }

    /**
     * 更新数据
     * @param $data
     * @param string $where
     * @return int
     */
    public function update($data, $where = '') {
        $edit_where = '';
        $where && $edit_where = 'WHERE '.$where;
        $fields = [];
        foreach ($data as $key=>$value) {
            $fields[] = "{$key}='{$value}'";
        }
        $edit_fields = implode(',', $fields);
        $sql = "UPDATE ".self::$table." SET $edit_fields $edit_where";
        $stmt =  self::$db->prepare($sql);
        $stmt->execute();
        $this->error($stmt, $sql);
        return $stmt->rowCount();
    }

    /**
     * 删除数据
     * @param $data
     * @param string $where
     * @return int
     */
    public function delete( $where = '') {
        $edit_where = '';
        $where && $edit_where = 'WHERE '.$where;
        $sql = "DELETE FROM ".self::$table." $edit_where";
        $stmt =  self::$db->prepare($sql);
        $stmt->execute();
        $this->error($stmt, $sql);
        return $stmt->rowCount();
    }


    public function find($sql) {
        $stmt = self::$db->prepare($sql);
        $stmt->execute();
        $this->error($stmt, $sql);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findAll($sql) {
        $stmt = self::$db->prepare($sql);
        $stmt->execute();
        $this->error($stmt, $sql);
        return $stmt->fetchAll(\PDO::FETCH_NAMED);
    }

    /**
     * @param \PDOStatement $stmt
     * @param $sql
     */
    public function error(\PDOStatement $stmt, $sql) {
        if ($stmt->errorCode() !== '00000') {
            $errorInfo = $stmt->errorInfo();
            throw SpiderException::err('error: '.end($errorInfo).', Sql: '.$sql );
        }
    }

}