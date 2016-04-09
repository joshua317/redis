<?php if (!defined('APP_PATH')) exit('No direct script access allowed');

/**
 * User: joshua317
 * Date: 15/1/18
 */
/*
   简易用法
   $config = ['host' => '127.0.0.1', 'port' => '6379', 'password' => 'xxxx', 'prefix' => 'app:'];
   $redis = Redis::getInstance($config);
   $redis->set('a',3333333);
   $a=($redis->get('a'));
 */
class Redis
{
    /**
     * Default config
     * @static
     * @var    array
     */
    protected static $_default_config = array(
        'socket_type' => 'tcp',
        'host' => '127.0.0.1',
        'password' => NULL,
        'port' => 6379,
        'timeout' => 0
    );
    /**
     * Redis connection
     * @var    Redis
     */
    protected $_redis = NULL;

    private static $_instance;


    /**
     * __construct
     * 私有, 单例模型，禁止外部构造
     * @param array $config
     * @author liujianghuai
     * @date 2016/03/30
     */
    private function __construct($config) {
        $config = array_merge(self::$_default_config, $config);
        $this->_redis = new Redis();
        try {
            if ($config['socket_type'] === 'unix') {
                $success = $this->_redis->connect($config['socket']);
            } else {//tcp socket
                $success = $this->_redis->pconnect($config['host'], $config['port'], $config['timeout']);
            }
            if (! $success) {
                log::write('Cache: Redis connection failed. Check your configuration.', 'error');
            }
            if (isset($config['password']) && !$this->_redis->auth($config['password'])) {
                log::write('Cache: Redis authentication failed.', 'error');
            }
            //加前缀
            isset($config['prefix']) && $this->_redis->setOption(Redis::OPT_PREFIX, $config['prefix']);

        } catch (RedisException $e) {
            log::write('Cache: Redis connection refused (' . $e->getMessage() . ')', 'error');
        }
    }


    /**
     * getInstance
     * 单例模型，构造函数
     * @param array $config
     * @return bool|object
     * @author joshua317
     * @date 15/1/18
     */
    public static function getInstance($config) {
        if (isset(self::$_instance)) {
            return self::$_instance;
        }
        if (!class_exists('Redis')) {
            throw new Exception("Class Redis not exists, please install the php Redis extension...");
        }
        if (isset($config)) {
            self::$_instance = new self($config);
            return self::$_instance;
        }
        return false;
    }

    /**
     *  私有, 单例模型，禁止克隆
     */
    private function __clone() {
    }

    /**
     *  公有，调用对象函数
     */
    public function __call($method, $args) {
        if (!$this->_redis || !$method) {
            return false;
        }
        if (!method_exists($this->_redis, $method)) {
            throw new Exception("Class RedisCli not have method ($method) ");
        }
        return call_user_func_array(array($this->_redis, $method), $args);
    }
}