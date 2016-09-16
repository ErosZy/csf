<?php

require_once BASEPATH . 'CoreHelper.php';

class Mongoose
{
    protected $_config = null;
    protected $_mongo = null;
    protected $_db = null;

    public function __construct($config = [])
    {
        $mongodb = CoreHelper::loadConfig("mongodb", "mongodb");
        $this->_config = array_merge($mongodb, $config);
        $this->initInstance();
    }

    protected function initInstance()
    {
        $mongodb = $this->_config;

        $options = [
            "db" => $mongodb["db"],
            "connect" => $mongodb["connect"],
        ];

        $username = $mongodb["username"];
        if ($username != "") {
            $options["username"] = $username;
        }

        $password = $mongodb["password"];
        if ($password != "") {
            $options["password"] = $password;
        }

        $this->_mongo = new MongoClient("mongodb://" . $mongodb["host"] . ":" . $mongodb["port"], $options);
        $this->_db = $this->_mongo->selectDB($mongodb["db"]);
    }

    public function __call($method, $args)
    {
        $callable = array($this->_db, $method);
        $result = null;

        try {
            $result = call_user_func_array($callable, $args);
        } catch (Exception $e) {
            if (method_exists($this->_mongo, 'close')) {
                $this->_mongo->close(true);
            }
            $this->initInstance();
            $result = call_user_func_array($callable, $args);
        } finally {
            return $result;
        }
    }

    public function __destruct()
    {
        CoreHelper::logMessage('info', 'mongo destruct...');
        if (method_exists($this->_mongo, 'close')) {
            $this->_mongo->close(true);
        }
    }
}