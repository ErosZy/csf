<?php

require_once BASEPATH . "CoreHelper.php";
require_once BASEPATH . "CoreAction.php";

class CoreRouter
{
    private static $_routeMap;

    public function __construct()
    {
        self::$_routeMap = CoreHelper::loadConfig("receive_routes", "router");
    }

    public function route(Array $params)
    {
        if (empty($params["router"])) {
            return;
        }

        $maps = self::$_routeMap;
        foreach ($maps as $key => $val) {
            if ($key == $params["router"]) {
                $val = str_replace('.php', '', trim($val, '/'));

                if (($lastSlash = strrpos($val, '/')) !== FALSE) {
                    $subdir = substr($val, 0, ++$lastSlash);
                    $router = substr($val, $lastSlash);
                } else {
                    $subdir = "";
                    $router = $val;
                }

                $instance = CoreHelper::loadClass($router, "actions/" . $subdir);
                if (
                    $instance instanceof CoreAction &&
                    method_exists($instance, 'distribute')
                ) {
                    $instance->distribute($params);
                }

                break;
            }
        }
    }
}