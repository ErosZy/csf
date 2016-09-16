<?php

require_once BASEPATH . 'CoreHelper.php';

class NSQ
{
    const NSQ_ADDRESS = '172.16.21.154';
    protected $_lookup = null;
    protected $_nsq = null;

    public function __construct(Array $params)
    {
        $this->_lookup = isset($params) ? $params[0] : null;
        $this->initInstance();
    }

    protected function initInstance()
    {
        $this->_nsq = new nsqphp\nsqphp($this->_lookup);
    }

    public function __call($method, $args)
    {
        $callable = array($this->_nsq, $method);
        $result = null;

        try {
            $result = call_user_func_array($callable, $args);
        } catch (Exception $e) {
            $this->_nsq = null;
            $this->initInstance();
            $result = call_user_func_array($callable, $args);
        } finally {
            return $result;
        }
    }

    public function __destruct()
    {
        CoreHelper::logMessage('info', 'nsq destruct...');
        $this->_nsq = null;
    }
}
