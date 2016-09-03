<?php

class DefaultAction extends CoreAction
{
    private $_actions = [
        "receives/Welcome"
    ];

    public function __construct()
    {
        foreach ($this->_actions as $val) {
            $this->addTarget($val);
        }
    }

    public function distribute(Array $params)
    {
        foreach ($this->_actions as $val) {
            $this->setParams($val, $params);
        }

        $this->pub();
    }
}