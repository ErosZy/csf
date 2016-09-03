<?php

class Benchmark
{
    public $test_func;
    public $process_num;
    public $request_num;
    public $server_url;
    public $server_config;
    public $read_len;
    public $time_end;
    private $shm_key;
    public $main_pid;
    public $child_pid = array();
    public $show_detail = false;
    public $max_write_time = 0;
    public $max_read_time = 0;
    public $max_conn_time = 0;
    public $pid;
    protected $tmp_dir = '/tmp/swoole_bench/';

    function __construct($func)
    {
        if (!function_exists($func)) {
            exit(__CLASS__ . ": function[$func] not exists\n");
        }
        if (!is_dir($this->tmp_dir)) {
            mkdir($this->tmp_dir);
        }
        $this->test_func = $func;
    }

    function end()
    {
        unlink($this->shm_key);
        foreach ($this->child_pid as $pid) {
            $f = $this->tmp_dir . 'lost_' . $pid . '.log';
            if (is_file($f)) unlink($f);
        }
    }

    function run()
    {
        $this->main_pid = posix_getpid();
        $this->shm_key = $this->tmp_dir . 't.log';
        for ($i = 0; $i < $this->process_num; $i++) {
            $this->child_pid[] = $this->start(array($this, 'worker'));
        }
        for ($i = 0; $i < $this->process_num; $i++) {
            $status = 0;
            $pid = pcntl_wait($status);
        }
        $this->time_end = microtime(true);
    }

    function init_signal()
    {
        pcntl_signal(SIGUSR1, array($this, "sig_handle"));
    }

    function sig_handle($sig)
    {
        switch ($sig) {
            case SIGUSR1:
                return;
        }
        $this->init_signal();
    }

    function start($func)
    {
        $pid = pcntl_fork();
        if ($pid > 0) {
            return $pid;
        } elseif ($pid == 0) {
            $this->worker();
        } else {
            echo "Error:fork fail\n";
        }
    }

    function worker()
    {
        $lost = 0;
        if (!file_exists($this->shm_key)) {
            file_put_contents($this->shm_key, microtime(true));
        }
        if ($this->show_detail) $start = microtime(true);
        $this->pid = posix_getpid();
        for ($i = 0; $i < $this->process_req_num; $i++) {
            $func = $this->test_func;
            if (!$func($this)) $lost++;
        }
        if ($this->show_detail) {
            $log = $this->pid . "#\ttotal_use(s):" . substr(microtime(true) - $start, 0, 5);
            $log .= "\tconnect(ms):" . substr($this->max_conn_time * 1000, 0, 5);
            $log .= "\twrite(ms):" . substr($this->max_write_time * 1000, 0, 5);
            $log .= "\tread(ms):" . substr($this->max_read_time * 1000, 0, 5);
            file_put_contents($this->tmp_dir . 'lost_' . $this->pid . '.log', $lost . "\n" . $log);
        } else {
            file_put_contents($this->tmp_dir . 'lost_' . $this->pid . '.log', $lost);
        }
        exit(0);
    }

    function report()
    {
        $time_start = file_get_contents($this->shm_key);
        $usetime = $this->time_end - $time_start;
        $lost = 0;
        foreach ($this->child_pid as $f) {
            $file = $this->tmp_dir . 'lost_' . $f . '.log';
            if (is_file($file)) {
                $_lost = file_get_contents($file);
                $log = explode("\n", $_lost, 2);
            }
            if (!empty($log)) {
                $lost += intval($log[0]);
                if ($this->show_detail) echo $log[1], "\n";
            }
        }

        echo "concurrency:\t" . $this->process_num, "\n";
        echo "request num:\t" . $this->request_num, "\n";
        echo "lost num:\t" . $lost, "\n";
        echo "success num:\t" . ($this->request_num - $lost), "\n";
        echo "total time:\t" . substr($usetime, 0, 5), "\n";
        echo "req per second:\t" . intval($this->request_num / $usetime), "\n";
        echo "one req use(ms):\t" . substr($usetime / $this->request_num * 1000, 0, 5), "\n";
    }
}