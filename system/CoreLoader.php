<?php

require_once BASEPATH . 'CoreHelper.php';

class CoreLoader
{
    protected $_ob_level;
    protected $_classes = [];
    protected $_models = [];
    protected $_libraries = [];
    protected $_helpers = [];

    public function __construct()
    {
        $this->_ob_level = ob_get_level();
        CoreHelper::logMessage('info', 'Loader Class Initialized');
    }

    public function helper($helpers = [])
    {
        foreach ($this->_prepFilename($helpers) as $helper) {
            if (isset($this->_helpers[$helper])) {
                continue;
            }

            $path = APPPATH . "helpers/" . $helper . ".php";
            if (!file_exists($path)) {
                CoreHelper::logMessage("error", 'Unable to load the requested file: helpers/' . $helper . '.php');
            }

            require_once($path);
            $this->_helpers[$helper] = true;
            CoreHelper::logMessage('info', 'Helper loaded: ' . $helper);

            if (!isset($this->_helpers[$helper])) {
                CoreHelper::logMessage("error", 'Unable to load the requested file: helpers/' . $helper . '.php');
            }
        }

        return $this;
    }

    public function library($library, $objName = null, $params = null)
    {
        if (empty($library)) {
            return $this;
        } elseif (is_array($library)) {
            foreach ($library as $key => $value) {
                if (is_int($key)) {
                    $this->library($value, $params);
                } else {
                    $this->library($key, $params, $value);
                }
            }

            return $this;
        }

        if ($params !== null && !is_array($params)) {
            $params = null;
        }

        $this->_loadLibrary($library, $params, $objName);
        return $this;
    }

    public function model($model, $name = '')
    {

        if (empty($model)) {
            return $this;
        } elseif (is_array($model)) {
            foreach ($model as $key => $value) {
                is_int($key) ? $this->model($value, '') : $this->model($key, $value);
            }

            return $this;
        }

        $path = '';

        if (($lastSlash = strrpos($model, '/')) !== false) {
            $path = substr($model, 0, ++$lastSlash);
            $model = substr($model, $lastSlash);
        }

        if (empty($name)) {
            $name = $model;
        }

        if (in_array($name, $this->_models, true)) {
            return $this;
        }

        $CN = &getInstance();
        if (isset($CN->$name)) {
            throw new RuntimeException('The model name you are loading is the name of a resource that is already being used: ' . $name);
        }

        if (!class_exists('CoreModel', false)) {
            CoreHelper::loadClass('CoreModel');
        }

        $model = ucfirst($model);
        if (!class_exists($model)) {
            $modelPath = APPPATH . "models/" . $path . $model . ".php";
            if (file_exists($modelPath)) {
                require_once($modelPath);
                if (!class_exists($model, false)) {
                    throw new RuntimeException($modelPath . " exists, but doesn't declare class " . $model);
                } else if (!class_exists($model, false)) {
                    throw new RuntimeException('Unable to locate the model you have specified: ' . $model);
                }
            }
        } elseif (!is_subclass_of($model, 'CoreModel')) {
            throw new RuntimeException("Class " . $model . " already exists and doesn't extend CI_Model");
        }

        $this->_models[] = $name;
        $CN->$name = new $model();
        return $this;
    }

    protected function _prepFilename($filename)
    {
        if (!is_array($filename)) {
            return array(str_replace(array('.php'), '', $filename));
        } else {
            foreach ($filename as $key => $val) {
                $filename[$key] = str_replace(array('.php'), '', $val);
            }

            return $filename;
        }
    }

    protected function _loadLibrary($class, $params = null, $objName = null)
    {
        $class = str_replace('.php', '', trim($class, '/'));

        if (($lastSlash = strrpos($class, '/')) !== FALSE) {
            $subdir = substr($class, 0, ++$lastSlash);
            $class = substr($class, $lastSlash);
        } else {
            $subdir = '';
        }

        $class = ucfirst($class);
        $filepath = APPPATH . 'libraries/' . $subdir . $class . '.php';

        if (class_exists($class, false)) {
            if ($objName !== null) {
                $CN = &getInstance();
                if (!isset($CN->$objName)) {
                    return $this->_initLibrary($class, '', $params, $objName);
                }
            }

            CoreHelper::logMessage('debug', $class . ' class already loaded. Second attempt ignored.');
            return;
        } else if (file_exists($filepath)) {
            require_once($filepath);
            return $this->_initLibrary($class, '', $params, $objName);
        }

        if ($subdir === '') {
            return $this->_loadLibrary($class . '/' . $class, $params, $objName);
        }

        CoreHelper::logMessage('error', 'Unable to load the requested class: ' . $class);
    }

    protected function _initLibrary($class, $prefix, $params = false, $objName = null)
    {
        $className = $prefix . $class;

        if (!class_exists($className, false)) {
            CoreHelper::logMessage('error', 'Non-existent class: ' . $className);
        }

        if (empty($objName)) {
            $objName = strtolower($class);
        }

        $CN = &getInstance();
        if (isset($CN->$objName)) {
            if ($CN->$objName instanceof $className) {
                CoreHelper::logMessage('debug', $className . " has already been instantiated as '" . $objName . "'. Second attempt aborted.");
                return;
            }
        }

        $this->_classes[$objName] = $class;

        $CN->$objName = isset($params)
            ? new $className($params)
            : new $className();

        return $this;
    }
}