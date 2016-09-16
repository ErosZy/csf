<?php

require_once BASEPATH . 'CoreHelper.php';

class CoreController
{
    public static $instance = null;

    public function __construct()
    {
        self::$instance = &$this;

        // 每个Controller持有一个单独的CoreLoader实例
        $this->load = CoreHelper::loadClass("CoreLoader", '', null, false);
        CoreHelper::logMessage('info', 'Controller Class Initialized');
    }
}
