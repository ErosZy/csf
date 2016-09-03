<?php

require_once BASEPATH . 'CoreHelper.php';

class CoreController
{
    private static $_instance = null;

    public function __construct()
    {
        self::$_instance = &$this;
        $this->load = CoreHelper::loadClass("CoreLoader");
        CoreHelper::logMessage('info', 'Controller Class Initialized');
    }

    public static function getInstance()
    {
        return self::$_instance;
    }
}
