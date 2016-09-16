<?php

require_once BASEPATH . 'CoreHelper.php';
require_once BASEPATH . 'CoreAnalysis.php';

class CoreWorker
{
    private $_router;
    private $_analysisRoutes;
    private $_closeRoutes;
    private $_connectRoutes;

    public function __construct()
    {
        $this->_router = CoreHelper::loadClass("CoreRouter");
        $this->_analysisRoutes = CoreHelper::loadConfig("analysis_routes", "router");
        $this->_closeRoutes = CoreHelper::loadConfig("close_routes", "router");
        $this->_connectRoutes = CoreHelper::loadConfig("connect_routes", "router");
    }

    public function onConnect(swoole_server $serv, $fd)
    {
        $conns = $this->_connectRoutes;

        foreach ($conns as $key => $val) {
            CoreHelper::loadClass($val, "controllers/connects", [
                "serv" => $serv,
                "fd" => $fd,
            ]);
        }
    }

    public function onReceive(swoole_server $serv, $fd, $fromId, $data)
    {
        $result = $this->_process($data);
        if (!(empty($result) || empty($result["router"]))) {
            $this->_router->route([
                "serv" => $serv,
                "fd" => $fd,
                "fromId" => $fromId,
                "data" => $result["data"],
                "router" => $result["router"]
            ]);
        }

    }

    public function onClose(swoole_server $serv, $fd)
    {
        $closes = $this->_closeRoutes;
        foreach ($closes as $key => $val) {
            CoreHelper::loadClass($val, "controllers/closes", [
                "serv" => $serv,
                "fd" => $fd,
            ]);
        }
    }

    protected function _process($data)
    {
        $analysises = $this->_analysisRoutes;
        foreach ($analysises as $key => $val) {
            $instance = CoreHelper::loadClass($val, "analysises");
            if (
                $instance instanceof CoreAnalysis &&
                method_exists($instance, "process")
            ) {
                $stop = false;
                $data = $instance->process($data, $stop);
                if ($stop) {
                    return $data;
                }
            }
        }
        return $data;
    }
}