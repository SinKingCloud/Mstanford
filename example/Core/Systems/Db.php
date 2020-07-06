<?php
/*
 * Title:Mysql操作类
 * Author:流逝中沉沦
 * QQ：1178710004
*/

namespace Systems;

use PDO;
use Systems\Errors;
use Systems\Config;

class Db
{
    protected static $_dbh = null;
    public static $configs;
    //静态属性,所有数据库实例共用,避免重复连接数据库
    protected $_dbType = 'mysql';
    protected $_pconnect = true;
    //是否使用长连接
    protected $_host = 'localhost';
    protected $_port = 3306;
    protected $_user = 'root';
    protected $_pass = 'root';
    protected $_dbName = null;
    protected $_prefix = '';
    //数据库名
    protected $_sql = false;
    //最后一条sql语句
    protected $_table = '';
    protected $_join = '';
    protected $_where = '';
    protected $_order = '';
    protected $_limit = '';
    protected $_field = '*';
    protected $_clear = 0;
    //状态，0表示查询条件干净，1表示查询条件污染
    protected static $_trans = 0;
    //事务指令数
    /**
     * 初始化类
     * @param array $conf 数据库配置
     */
    public function __construct(array $conf = null)
    {
        if ($conf != null) {
            $this->config = $conf;
        }else {
            if(!isset(self::$configs['database'])||empty(self::$configs['database'])) {
                self::$configs = Config::get();
            }
            $this->config = self::$configs;
        }
        class_exists('PDO') or Errors::show("PDO: class not exists");
        $this->_host = $this->config['database']['database_host'];
        $this->_port = $this->config['database']['database_port'];
        $this->_user = $this->config['database']['database_user'];
        $this->_pass = $this->config['database']['database_pwd'];
        $this->_dbName = $this->config['database']['database_name'];
        $this->_prefix = $this->config['database']['database_prefix'];
        //连接数据库
        if (is_null(self::$_dbh)) {
            $this->_connect();
        }
    }
    /*
     * 静态化Db
     */
    public static function init($config = null)
    {
        if ($config != null) {
            return new self($config);
        } else {
            return new self();
        }
    }
    /**
     * 连接数据库的方法
     */
    protected function _connect()
    {
        $dsn = $this->_dbType . ':host=' . $this->_host . ';port=' . $this->_port . ';dbname=' . $this->_dbName;
        $options = $this->_pconnect ? array(PDO::ATTR_PERSISTENT => true) : array();
        try {
            $dbh = new PDO($dsn, $this->_user, $this->_pass, $options);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //设置如果sql语句执行错误则抛出异常，事务会自动回滚
            $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            //禁用prepared statements的仿真效果(防SQL注入)
        } catch (PDOException $e) {
            Errors::show($e);
        }
        $dbh->exec('SET NAMES utf8');
        self::$_dbh = $dbh;
    }
    /** 
     * 字段和表名添加 `符号
     * 保证指令中使用关键字不出错 针对mysql 
     * @param string $value 
     * @return string 
     */
    protected function _addChar($value)
    {
        if ('*' == $value || false !== strpos($value, '(') || false !== strpos($value, ')') || false !== strpos($value, '.') || false !== strpos($value, '`') || is_numeric($value)) {
            //如果包含* 或者 使用了sql方法 则不作处理
        } elseif (false === strpos($value, '`')) {
            $value = '`' . trim($value) . '`';
        }
        return $value;
    }
    /** 
     * 取得数据表的字段信息 
     * @param string $tbName 表名
     * @return array 
     */
    protected function _tbFields($tbName)
    {
        $sql = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME="' . $tbName . '" AND TABLE_SCHEMA="' . $this->_dbName . '"';
        $stmt = self::$_dbh->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ret = array();
        foreach ($result as $key => $value) {
            $ret[$value['COLUMN_NAME']] = 1;
        }
        return $ret;
    }
    /** 
     * 过滤并格式化数据表字段
     * @param string $tbName 数据表名 
     * @param array $data POST提交数据 
     * @return array $newdata 
     */
    protected function _dataFormat($tbName, $data)
    {
        if (!is_array($data)) {
            return array();
        }
        $table_column = $this->_tbFields($tbName);
        $ret = array();
        foreach ($data as $key => $val) {
            if (!is_scalar($val)) {
                continue;
            }
            //值不是标量则跳过
            if (array_key_exists($key, $table_column)) {
                $key = $this->_addChar($key);
                if (is_int($val)) {
                    $val = intval($val);
                } elseif (is_float($val)) {
                    $val = floatval($val);
                } elseif (preg_match('/^\\(\\w*(\\+|\\-|\\*|\\/)?\\w*\\)$/i', $val)) {
                    // 支持在字段的值里面直接使用其它字段 ,例如 (score+1) (name) 必须包含括号
                    $val = $val;
                } elseif (is_string($val)) {
                    $val = '"' . addslashes($val) . '"';
                }
                $ret[$key] = $val;
            }
        }
        return $ret;
    }
    /**
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * @param string $sql sql指令 
     * @return mixed 
     */
    protected function _doQuery($sql = '')
    {
        $this->_sql = $sql;
        $pdostmt = self::$_dbh->prepare($this->_sql);
        //prepare或者query 返回一个PDOStatement
        $pdostmt->execute();
        $result = $pdostmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    /** 
     * 执行语句 针对 INSERT, UPDATE 以及DELETE,exec结果返回受影响的行数
     * @param string $sql sql指令 
     * @return integer 
     */
    protected function _doExec($sql = '')
    {
        $this->_sql = $sql;
        return self::$_dbh->exec($this->_sql);
    }
    /** 
     * 执行sql语句，自动判断进行查询或者执行操作 
     * @param string $sql SQL指令 
     * @return mixed 
     */
    public function query($sql = '')
    {
        $sql = str_replace("{DB_prefix}", $this->_prefix, $sql);
        $queryIps = 'INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|LOAD DATA|SELECT .* INTO|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK';
        if (preg_match('/^\\s*"?(' . $queryIps . ')\\s+/i', $sql)) {
            return $this->_doExec($sql);
        } else {
            //查询操作
            return $this->_doQuery($sql);
        }
    }
    /** 
     * 获取最近一次查询的sql语句 
     * @return String 执行的SQL 
     */
    public function getLastSql()
    {
        return $this->_sql;
    }
    /**
     * 插入方法
     * @param array $data 字段-值的一维数组
     * @return int 受影响的行数
     */
    public function insert(array $data)
    {
        $data = $this->_dataFormat($this->_table, $data);
        if (!$data) {
            return;
        }
        $sql = "insert into " . $this->_table . "(" . implode(',', array_keys($data)) . ") values(" . implode(',', array_values($data)) . ")";
        return $this->_doExec($sql);
    }
    /**
     * 删除方法
     * @return int 受影响的行数
     */
    public function delete()
    {
        //安全考虑,阻止全表删除
        if (!trim($this->_where)) {
            return false;
        }
        $sql = "delete from " . $this->_table . " " . $this->_where;
        $this->_clear = 1;
        $this->_clear();
        return $this->_doExec($sql);
    }
    /**
     * 更新函数
     * @param array $data 参数数组
     * @return int 受影响的行数
     */
    public function update(array $data)
    {
        //安全考虑,阻止全表更新
        if (!trim($this->_where)) {
            return false;
        }
        $data = $this->_dataFormat($this->_table, $data);
        if (!$data) {
            return;
        }
        $valArr = array();
        foreach ($data as $k => $v) {
            $valArr[] = $k . '=' . $v;
        }
        $val = implode(',', $valArr);
        $sql = "update " . trim($this->_table) . " set " . trim($val) . " " . trim($this->_where);
        return $this->_doExec($sql);
    }
    /**
     * 获取表名（无前缀）
     * @param string $tbName 操作的数据表名
     * @return array 结果集
     */
    public function table($tbName = '')
    {
        $this->_table = $tbName;
        return $this;
    }
    /**
     * 获取表名（有前缀）
     * @param string $tbName 操作的数据表名
     * @return array 结果集
     */
    public function name($tbName = '')
    {
        $this->_table = $this->_prefix . $tbName;
        return $this;
    }
    /**
     * 联查函数
     */
    public function join($tbName, $left = null)
    {
        if (!isset($left)) {
            $left = "";
        }
        if (is_array($tbName)) {
            $temp = "";
            foreach ($tbName as $key) {
                $temp .= " {$left} join " . $this->_prefix . "{$key}";
            }
            $this->_join = $temp;
            unset($temp);
        } else {
            $tbName = $this->_prefix . $tbName;
            $this->_join = " {$left} join {$tbName}";
        }
        return $this;
    }
    /**
     * 查询函数
     * @return array 结果集
     */
    public function select()
    {
        $sql = "select " . trim($this->_field) . " from " . trim($this->_table) . " " . trim($this->_join) . " " . trim($this->_where) . " " . trim($this->_order) . " " . trim($this->_limit);
        //echo $sql.'</br>';
        $this->_clear = 1;
        $this->_clear();
        return $this->_doQuery(trim($sql));
    }
    /**
     * 查询函数(单条记录)
     * @return array 记录信息
     */
    public function find()
    {
        $sql = "select " . trim($this->_field) . " from " . trim($this->_table) . " " . trim($this->_join) . " " . trim($this->_where) . " " . trim($this->_order) . " " . trim($this->_limit);
        //echo $sql.'</br>';
        $this->_clear = 1;
        $this->_clear();
        if (empty($this->_doQuery(trim($sql))[0])) {
            return null;
        }
        return $this->_doQuery(trim($sql))[0];
    }
    /**
     * @param mixed $option 组合条件的二维数组，例：$option['field1'] = array(1,'=>','or')
     * @return $this
     */
    public function where($option)
    {
        if ($this->_clear > 0) {
            $this->_clear();
        }
        $this->_where = ' where ';
        $logic = 'and';
        if (is_string($option)) {
            $this->_where .= $option;
        } elseif (is_array($option)) {
            foreach ($option as $k => $v) {
                if (is_array($v)) {
                    $relative = isset($v[1]) ? $v[1] : '=';
                    $logic = isset($v[2]) ? $v[2] : 'and';
                    $condition = ' (' . $this->_addChar($k) . ' ' . $relative . ' \'' . $v[0] . '\') ';
                } else {
                    $logic = 'and';
                    $condition = ' (' . $this->_addChar($k) . '=\'' . $v . '\') ';
                }
                $this->_where .= isset($mark) ? $logic . $condition : $condition;
                $mark = 1;
            }
        }
        return $this;
    }
    /**
     * 设置排序
     * @param mixed $option 排序条件数组 例:array('sort'=>'desc')
     * @return $this
     */
    public function order($option)
    {
        if ($this->_clear > 0) {
            $this->_clear();
        }
        $this->_order = ' order by ';
        if (is_string($option)) {
            $this->_order .= $option;
        } elseif (is_array($option)) {
            foreach ($option as $k => $v) {
                $order = $this->_addChar($k) . ' ' . $v;
                $this->_order .= isset($mark) ? ',' . $order : $order;
                $mark = 1;
            }
        }
        return $this;
    }
    /**
     * 设置查询行数及页数
     * @param int $page pageSize不为空时为页数，否则为行数
     * @param int $pageSize 为空则函数设定取出行数，不为空则设定取出行数及页数
     * @return $this
     */
    public function limit($page, $pageSize = null)
    {
        if ($this->_clear > 0) {
            $this->_clear();
        }
        if ($pageSize === null) {
            $this->_limit = "limit " . $page;
        } else {
            $pageval = intval(($page - 1) * $pageSize);
            $this->_limit = "limit " . $pageval . "," . $pageSize;
        }
        return $this;
    }
    /**
     * 设置分页查询行数及页数
     */
    public function page($page = 1, $pageSize = 10)
    {
        if ($this->_clear > 0) {
            $this->_clear();
        }
        if ($pageSize === null) {
            $this->_limit = "limit " . $page;
        } else {
            $pageval = intval(($page - 1) * $pageSize);
            $this->_limit = "limit " . $pageval . "," . $pageSize;
        }
        $sql = "select " . trim($this->_field) . " from " . trim($this->_table) . " " . trim($this->_join) . " " . trim($this->_where) . " " . trim($this->_order) . " " . trim($this->_limit);
        $sql2 = trim("SELECT COUNT(*) FROM " . trim($this->_table) . " " . trim($this->_join) . " " . trim($this->_where) . " " . trim($this->_order));
        $this->_clear = 1;
        $this->_clear();
        // echo $sql."</br>";
        $total = $this->_doQuery($sql2);
        $return = array();
        $return['Data'] = $this->_doQuery(trim($sql));
        $return['Total'] = intval($total[0]['COUNT(*)']);
        $return['TotalPages'] = intval($return['Total'] % $pageSize == 0 ? $return['Total'] / $pageSize : $return['Total'] / $pageSize + 1);
        $return['NowPages'] = $page;
        $return['NowTotal'] = count($return['Data']);
        $return['PageSize'] = $pageSize;
        return $return;
    }
    /**
     * 统计数量
     */
    public function count()
    {
        if ($this->_clear > 0) {
            $this->_clear();
        }
        $sql = "select COUNT(" . trim($this->_field) . ") from " . trim($this->_table) . " " . trim($this->_join) . " " . trim($this->_where) . " " . trim($this->_order) . " " . trim($this->_limit);
        $this->_clear = 1;
        $this->_clear();
        return $this->_doQuery($sql)[0]['COUNT(*)'];
    }
    /**
     * 设置查询字段
     * @param mixed $field 字段数组
     * @return $this
     */
    public function field($field)
    {
        if ($this->_clear > 0) {
            $this->_clear();
        }
        if (is_string($field)) {
            $field = explode(',', $field);
        }
        $nField = array_map(array($this, '_addChar'), $field);
        $this->_field = implode(',', $nField);
        return $this;
    }
    /**
     * 清理标记函数
     */
    protected function _clear()
    {
        $this->_where = '';
        $this->_order = '';
        $this->_limit = '';
        $this->_field = '*';
        $this->_clear = 0;
    }
    /**
     * 手动清理标记
     * @return $this
     */
    public function clearKey()
    {
        $this->_clear();
        return $this;
    }
    /**
     * 启动事务 
     * @return void 
     */
    public function startTrans()
    {
        //数据rollback 支持
        if (self::$_trans == 0) {
            self::$_dbh->beginTransaction();
        }
        self::$_trans++;
        return;
    }
    /** 
     * 用于非自动提交状态下面的查询提交 
     * @return boolen 
     */
    public function commit()
    {
        $result = true;
        if (self::$_trans > 0) {
            $result = self::$_dbh->commit();
            self::$_trans = 0;
        }
        return $result;
    }
    /** 
     * 事务回滚 
     * @return boolen 
     */
    public function rollback()
    {
        $result = true;
        if (self::$_trans > 0) {
            $result = self::$_dbh->rollback();
            self::$_trans = 0;
        }
        return $result;
    }
    /**
     * 关闭连接
     * PHP 在脚本结束时会自动关闭连接。
     */
    public function close()
    {
        if (!is_null(self::$_dbh)) {
            self::$_dbh = null;
        }
    }
}
