<?php

require_once BASEPATH . 'CoreHelper.php';

class Database
{
    protected $_connection = null;
    protected $_db = null;

    public function __construct()
    {
        $database = CoreHelper::loadConfig("database", "database");
        $dsn = "mysql:host=" . $database["host"] . ";dbname=" . $database["dbname"];
        $user = $database["user"];
        $password = $database["password"];
        $this->_connection = new Nette\Database\Connection($dsn, $user, $password, ["lazy" => true]);
        $cacheMemoryStorage = new Nette\Caching\Storages\MemoryStorage;
        $structure = new Nette\Database\Structure($this->_connection, $cacheMemoryStorage);
        $conventions = new Nette\Database\Conventions\DiscoveredConventions($structure);
        $this->_db = new Nette\Database\Context($this->_connection, $structure, $conventions, $cacheMemoryStorage);
    }

    public function __destruct()
    {
        CoreHelper::logMessage('info', 'database destruct...');
        $this->_connection->disconnect();
    }

    public function __call($method, $args)
    {
        $callable = array($this->_db, $method);
        return call_user_func_array($callable, $args);
    }
}
