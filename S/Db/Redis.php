<?php
namespace S\Db;

use \S\Strace\Timelog as Timelog;

/**
 * ################################
 * # redis 使用说明
 * ################################
 * $redis = new \S\Db\Redis();
 * $value = $redis->get($key);
 *
 * Class Redis
 *
 * @package S\Db
 */
class Redis {

    const   DEFAULT_CONNECT_TIMEOUT = 5,
        DEFAULT_SEND_TIMEOUT = 5,
        DEFAULT_RECV_TIMEOUT = 5;

    const NAME_DEFAULT = 'common';

    /**
     * @var \Redis[]
     */
    protected static $res = array();
    protected $config = array();
    protected $name = '';

    /**
     * 用于判断是否使用持久连接，如果使用，析构函数中不做close操作
     *
     * @var bool
     */
    private $_persistent = false;

    /**
     * @param string $name
     */
    public function __construct($name = self::NAME_DEFAULT) {
        $this->name = $name;
    }

    /**
     * 将调用均转向到封装的Redis实例
     *
     * @param       $name
     * @param array $args
     *
     * @return mixed
     * @throws \S\Exception
     */
    public function __call($name, $args = array()) {
        Timelog::instance()->resetTime();
        try {
            $ret = call_user_func_array(array($this->getInstance(), $name), $args);
        } catch (\Exception $e) {
            //发生异常清除会话handle
            $this->close();
            //异常处理
            if ($e instanceof \S\Exception) {
                throw $e;
            } else {
                throw new \S\Exception($e->getCode() . ' ' . $e->getMessage());
            }
        }
        $this->_setStrace($name, $args);

        return $ret;
    }

    /**
     * 去掉危险操作的功能
     *
     * @return bool
     */
    public function flush() {
        return false;
    }

    public function close() {
        (self::$res[$this->name] && isset(self::$res[$this->name]->socket)) && self::$res[$this->name]->close();
        self::$res[$this->name] = null;

        return true;
    }

    public function __destruct() {
        if (!$this->_persistent) {
            $this->close();
        }
    }

    /**
     * @return \Redis
     * @throws \S\Exception
     */
    protected function getInstance() {
        if (!isset(self::$res[$this->name])) {
            self::$res[$this->name] = new \Redis();
            $this->configure();
            $this->connect();
            $this->setOptions();
        }

        /**
         * @var /Redis $res[$this->name]
         */
        return self::$res[$this->name];
    }

    /**
     * 初始化配置信息，以addServers方式使用
     */
    protected function configure() {
        $this->config = \S\Config::confServer('redis.' . $this->name);
        if (!$this->config) {
            throw new \S\Exception(get_class($this) . ' need be configured. Config : ' . $this->name);
        }
    }

    protected function connect() {
        if (isset($this->config['persistent']) && $this->config['persistent']) {
            $this->_persistent = true;
            $conn              = self::$res[$this->name]->pconnect($this->config['host'], $this->config['port'], $this->config['timeout'] ?: self::DEFAULT_CONNECT_TIMEOUT);
            //重连一次
            if (false === $conn) {
                $conn = self::$res[$this->name]->pconnect($this->config['host'], $this->config['port'], $this->config['timeout'] ?: self::DEFAULT_CONNECT_TIMEOUT);
            }
        } else {
            $conn = self::$res[$this->name]->connect($this->config['host'], $this->config['port'], $this->config['timeout'] ?: self::DEFAULT_CONNECT_TIMEOUT);
            //重连一次
            if (false === $conn) {
                $conn = self::$res[$this->name]->connect($this->config['host'], $this->config['port'], $this->config['timeout'] ?: self::DEFAULT_CONNECT_TIMEOUT);
            }
        }

        if (false === $conn) {
            throw new \S\Exception("redis connect " . $this->config['host'] . " fail");
        }

        return true;
    }

    protected function setOptions() {
        if (isset($this->config['user']) && $this->config['user'] && $this->config['auth']) {
            if (self::$res[$this->name]->auth($this->config['user'] . ":" . $this->config['auth']) == false) {
                throw new \S\Exception("redis auth " . $this->config['host'] . " fail");
            }
        }
        if (!isset($this->config['user']) && isset($this->config['auth']) && $this->config['auth']) {
            self::$res[$this->name]->auth($this->config['auth']);
        }
        if (isset($this->config['db']) && $this->config['db']) {
            self::$res[$this->name]->select($this->config['db']);
        }
    }

    /**
     * @param $function
     * @param $params
     */
    private function _setStrace($function, $params) {
        Timelog::instance()->log('redis', array(
            'class'    => __CLASS__,
            'method'   => $function,
            'resource' => $this->name,
            'params'   => $params,
        ));
    }

}