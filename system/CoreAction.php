<?php

require_once BASEPATH . 'CoreHelper.php';

class CoreAction
{
    private $_targets = [];

    public function addTarget($className)
    {
        $this->_targets[$className] = null;
    }

    public function setParams($className, Array $params)
    {
        if (key_exists($className, $this->_targets)) {
            $this->_targets[$className] = $params;
        }
    }

    public function removeTarget($className)
    {
        unset($this->_targets[$className]);
    }

    public function clearTargets()
    {
        $this->_targets = [];
    }

    public function pub()
    {
        if (count($this->_targets) > 0) {
            $maps = $this->_targets;
            foreach ($maps as $key => $val) {
                $instance = CoreHelper::loadClass($key, "controllers");
                if (method_exists($instance, 'process')) {
                    $instance->process($val);
                }
            }
        }
    }
}