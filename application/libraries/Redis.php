<?php

require_once BASEPATH . 'CoreHelper.php';

class Redis
{
    protected $_config = null;
    protected $_redis = null;

    public function __construct()
    {
        $this->_config = CoreHelper::loadConfig("redis", "redis");
        $this->initInstance();
    }

    protected function initInstance()
    {
        $this->_redis = new Predis\Client($this->_config);
    }

    public function __call($method, $args)
    {
        $callable = array($this->_redis, $method);
        $result = null;

        try {
            $result = call_user_func_array($callable, $args);
        } catch (Exception $e) {
            if (method_exists($this->_redis, 'disconnect')) {
                $this->_redis->disconnect();
            }
            $this->initInstance();
            $result = call_user_func_array($callable, $args);
        } finally {
            return $result;
        }
    }

    public function __destruct()
    {
        CoreHelper::logMessage('info', 'redis destruct...');
        if (method_exists($this->_redis, 'disconnect')) {
            $this->_redis->disconnect();
        }
    }
}