<?php

require_once BASEPATH . 'CoreHelper.php';

class NSQ
{
    const NSQ_ADDRESS = '172.16.20.173';
    protected $nsq = null;

    public function __construct(Array $params)
    {
        $lookup = isset($params) ? $params[0] : null;
        $this->nsq = new nsqphp\nsqphp($lookup);
    }

    public function __destruct()
    {
        CoreHelper::logMessage('info', 'nsq destruct...');
        $this->nsq = null;
    }

    public function __call($method, $args)
    {
        $callable = array($this->nsq, $method);
        return call_user_func_array($callable, $args);
    }
}
