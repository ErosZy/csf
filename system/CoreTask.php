<?php

class CoreTask
{
    public function onTask(swoole_server $serv, $taskId, $fromId, $data)
    {
        if (isset($data["controller"])) {
            $controller = $data["controller"];
            $method = $data["method"];
            $data = $data["data"];
            $instance = CoreHelper::loadClass($controller, "controllers/tasks");
            if (method_exists($instance, $method)) {
                $instance->$method($serv, $taskId, $fromId, $data);
            }
        }
    }

    public function onFinish(swoole_server $serv, $taskId, $data)
    {
        // sth to do...
    }
}