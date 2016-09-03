<?php

require_once BASEPATH . 'CoreHelper.php';

class Mongo
{
    protected $mongo = null;
    protected $db = null;

    public function __construct($config = [])
    {
        $mongodb = CoreHelper::loadConfig("mongodb", "mongodb");
        $mongodb = array_merge($mongodb, $config);
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

        $this->mongo = new MongoClient("mongodb://" . $mongodb["host"] . ":" . $mongodb["port"], $options);
        $this->db = $this->mongo->selectDB($mongodb["db"]);
    }

    public function __destruct()
    {
        CoreHelper::logMessage('info', 'mongo destruct...');
        $this->mongo->close(true);
    }

    public function __call($method, $args)
    {
        $callable = array($this->db, $method);
        return call_user_func_array($callable, $args);
    }
}