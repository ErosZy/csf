<?php

require_once BASEPATH . 'CoreHelper.php';

class Redis
{
    protected $redis = null;

    public function __construct()
    {
        $config = CoreHelper::loadConfig("redis", "redis");
        $this->redis = new Predis\Client($config);
    }

    public function __destruct()
    {
        CoreHelper::logMessage('info', 'redis destruct...');
        $this->redis->disconnect();
    }

    public function __call($method, $args)
    {
        $callable = array($this->redis, $method);
        return call_user_func_array($callable, $args);
    }
}