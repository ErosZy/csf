<?php

require_once BASEPATH . 'CoreHelper.php';

class Cached
{
    protected $_config = null;
    protected $_memcached = null;

    public function __construct()
    {
        $this->_config = CoreHelper::loadConfig('memcached', 'memcached');
        $this->initInstance();
    }

    protected function initInstance()
    {
        $this->_memcached = new Memcached();
        $this->_memcached->addServer(
            $this->_config['host'],
            $this->_config['port'],
            $this->_config['weight']
        );
    }

    protected function get($id)
    {
        $data = $this->_memcached->get($id);
        return is_array($data) ? $data[0] : $data;
    }

    protected function save($id, $data, $ttl = 60, $raw = false)
    {
        if ($raw !== true) {
            $data = array($data, time(), $ttl);
        }
        return $this->_memcached->set($id, $data, $ttl);
    }

    protected function delete($id)
    {
        return $this->_memcached->delete($id);
    }

    protected function increment($id, $offset = 1)
    {
        return $this->_memcached->increment($id, $offset);
    }

    protected function decrement($id, $offset = 1)
    {
        return $this->_memcached->decrement($id, $offset);
    }

    protected function clean()
    {
        return $this->_memcached->flush();
    }

    protected function getMetadata($id)
    {
        $stored = $this->_memcached->get($id);

        if (count($stored) !== 3) {
            return false;
        }

        list($data, $time, $ttl) = $stored;
        return [
            'expire' => $time + $ttl,
            'mtime' => $time,
            'data' => $data
        ];
    }

    protected function isSupported()
    {
        return (extension_loaded('memcached') OR extension_loaded('memcache'));
    }

    public function __call($method, $args)
    {
        $callable = array($this, $method);
        $result = null;

        try {
            $result = call_user_func_array($callable, $args);
        } catch (Exception $e) {
            if (method_exists($this->_memcached, 'quit')) {
                $this->_memcached->quit();
            }
            $this->initInstance();
            $result = call_user_func_array($callable, $args);
        } finally {
            return $result;
        }
    }

    public function __destruct()
    {
        CoreHelper::logMessage('info', 'memcached destruct...');
        if (method_exists($this->_memcached, 'quit')) {
            $this->_memcached->quit();
        }
    }
}
