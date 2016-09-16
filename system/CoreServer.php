<?php

require_once BASEPATH . 'CoreHelper.php';
require_once BASEPATH . 'CoreWorker.php';
require_once BASEPATH . 'CoreTask.php';

class CoreServer
{
    private static $_instance = null;
    private static $_serv = null;
    private static $_config = null;
    private static $_workers = [];
    private static $_tasks = [];
    private static $_senderRoutes = [];

    public static function run()
    {
        if (!self::$_instance) {
            self::$_instance = new CoreServer();
        }

        self::$_config = CoreHelper::loadConfig('swoole', 'config');
        self::$_senderRoutes = CoreHelper::loadConfig("process_routes", "router");

        self::$_serv = new swoole_server(self::$_config["host"], self::$_config["port"]);
        self::$_serv->set(self::$_config);
        self::$_serv->on("start", [self::$_instance, "onStart"]);
        self::$_serv->on('workerStart', [self::$_instance, "onWorkerStart"]);
        self::$_serv->on('workerStop', [self::$_instance, "onWorkerStop"]);
        self::$_serv->on("connect", [self::$_instance, "onConnect"]);
        self::$_serv->on('receive', [self::$_instance, 'onReceive']);
        self::$_serv->on("close", [self::$_instance, "onClose"]);
        self::$_serv->on("task", [self::$_instance, "onTask"]);
        self::$_serv->on("finish", [self::$_instance, "onFinish"]);

        if (!empty(self::$_senderRoutes)) {
            self::createProcessSender(self::$_senderRoutes);
        }

        self::$_serv->start();
    }

    public function onStart()
    {
        echo "server start, listening at " . self::$_config["port"] . "...\n";
    }

    public function onWorkerStart(swoole_server $serv, $workerId)
    {
        require_once BASEPATH . 'CoreEnvSetting.php';

        if ($workerId >= $serv->setting['worker_num']) {
            self::$_tasks[$workerId] = new CoreTask();
        } else {
            self::$_workers[$workerId] = new CoreWorker();
        }
    }

    public function onWorkerStop(swoole_server $serv, $workerId)
    {
        if ($workerId >= $serv->setting['worker_num']) {
            unset(self::$_tasks[$workerId]);
        } else {
            unset(self::$_workers[$workerId]);
        }
    }

    public function onConnect(swoole_server $serv, $fd)
    {
        $worker = self::getWorker($serv->worker_id);
        $worker && $worker->onConnect($serv, $fd);
    }

    public function onReceive(swoole_server $serv, $fd, $fromId, $data)
    {
        $worker = self::getWorker($serv->worker_id);
        $worker && $worker->onReceive($serv, $fd, $fromId, $data);
    }

    public function onClose(swoole_server $serv, $fd)
    {
        $worker = self::getWorker($serv->worker_id);
        $worker && $worker->onClose($serv, $fd);
    }

    public function onTask(swoole_server $serv, $taskId, $fromId, $data)
    {
        $task = self::getTask($serv->worker_id);
        $task && $task->onTask($serv, $taskId, $fromId, $data);
    }

    public function onFinish(swoole_server $serv, $taskId, $data)
    {
        $task = self::getTask($taskId);
        $task && $task->onFinish($serv, $taskId, $data);
    }

    private static function createProcessSender(Array $routes)
    {
        $workerNum = count($routes);
        $serv = self::$_serv;

        for ($i = 0; $i < $workerNum; $i++) {
            $class = $routes[$i];
            $process = new swoole_process(function () use ($class, $serv) {
                require_once BASEPATH . 'CoreEnvSetting.php';
                $instance = CoreHelper::loadClass($class, "controllers/processes");
                $instance->process($serv);
            });
            $serv->addProcess($process);
        }
    }

    private static function getWorker($workerId)
    {
        return self::$_workers[$workerId];
    }

    private static function getTask($taskId)
    {
        return self::$_tasks[$taskId];
    }
}
