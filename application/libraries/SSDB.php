<?php

require_once BASEPATH . 'CoreHelper.php';

class SSDB
{
    protected $_config = null;
    protected $_ssdb = null;

    public function __construct()
    {
        $this->_config = CoreHelper::loadConfig("ssdb", "ssdb");
	    $this->initInstance();
    }

    protected function initInstance()
    {
        $ssdb = $this->_config;
        $this->_ssdb = new SSDB\Client($ssdb["host"], $ssdb["port"]);
    }

    public function __call($method, $args)
    {
        $callable = array($this->_ssdb, $method);
        $result = null;
        try {
            $result = call_user_func_array($callable, $args);
        } catch (Exception $e) {
            if (method_exists($this->_ssdb, 'close')) {
                $this->_ssdb->close();
            }
            $this->initInstance();
            $result = call_user_func_array($callable, $args);
        } finally {
            return $result;
        }
    }
    
    public function __destruct()
    {
        CoreHelper::logMessage('info', 'ssdb destruct...');
        if (method_exists($this->_ssdb, 'close')) {
            $this->_ssdb->close();
        }
    }
}
