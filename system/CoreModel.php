<?php

require_once BASEPATH . 'CoreHelper.php';

class CoreModel
{
    public function __construct()
    {
        CoreHelper::logMessage('info', 'Model Class Initialized');
    }

    public function __get($key)
    {
        return getInstance()->$key;
    }
}