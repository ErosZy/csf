<?php

require_once BASEPATH . 'CoreController.php';

class CoreHelper
{
    private static $_isPHP = [];
    private static $_classes = [];
    private static $_config = [];
    private static $_log = null;

    public static function isPHP($version)
    {
        $version = (string)$version;
        if (!isset(self::$_isPHP[$version])) {
            self::$_isPHP[$version] = version_compare(PHP_VERSION, $version, '>=');
        }
        return self::$_isPHP[$version];
    }

    public static function isWriteable($file)
    {
        if (DIRECTORY_SEPARATOR === '/' && (self::isPHP('5.4') OR !ini_get('safe_mode'))) {
            return is_writable($file);
        }

        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) OR ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }

        fclose($fp);
        return TRUE;
    }

    public static function &loadClass($class, $directory = '', $param = null, $isCache = true)
    {
        if (isset(self::$_classes[$class])) {
            $class = self::$_classes[$class];
            if(is_subclass_of($class,'CoreController')){
                CoreController::$instance = $class;
            }
            return $class;
        }

        $name = false;

        foreach (array(APPPATH, BASEPATH) as $path) {
            if (file_exists($path . $directory . '/' . $class . '.php')) {
                $name = $class;

                if (class_exists($name, FALSE) === FALSE) {
                    require_once($path . $directory . '/' . $class . '.php');
                }

                break;
            }
        }

        if ($name === FALSE) {
            die('Unable to locate the specified class: ' . $class . '.php');
        } else {
            $class = str_replace('.php', '', trim($class, '/'));

            if (($lastSlash = strrpos($class, '/')) !== FALSE) {
                $name = substr($class, ++$lastSlash);
            }
        }

        $instance = isset($param)
            ? new $name($param)
            : new $name();

        if ($isCache) {
            self::$_classes[$class] = $instance;
        }

        return $instance;
    }

    public static function loadConfig($name, $section = null)
    {
        if (!isset(self::$_config[$name])) {
            $path = APPPATH . "config/" . $section . ".php";
            if (file_exists($path)) {
                $config = [];
                require_once($path);
                foreach ($config as $key => $val) {
                    self::$_config[$key] = $val;
                }
            }
        }

        return isset(self::$_config[$name]) ?
            self::$_config[$name] :
            die("load config error,can't find " . $section . "'s " . $name);
    }

    public static function logMessage($level, $message)
    {
        if (self::$_log === NULL) {
            self::$_log = self::loadClass('CoreLog');
        }

        self::$_log->writeLog($level, $message);
    }
}