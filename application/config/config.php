<?php

$config["charset"] = "UTF-8";
$config["composer_autoload"] = true;

/**
 * server config load
 * detail found: http://wiki.swoole.com/wiki/page/274.html
 */
$config["swoole"]["host"] = "0.0.0.0";
$config["swoole"]["port"] = 8083;
$config["swoole"]["daemonize"] = false;
$config["swoole"]["worker_num"] = 10;
$config["swoole"]["max_request"] = 10000; // beyond this number,worker process die
$config["swoole"]["max_conn"] = 1000; // reject other connections while beyond the max_conn number
$config["swoole"]["dispatch_mode"] = 2;
$config["swoole"]["task_worker_num"] = 10;
$config["swoole"]["task_ipc_mode"] = 3;
$config["swoole"]["task_max_request"] = 1000;
$config["swoole"]["task_tmpdir"] = "/tmp/task_tmpdir";
$config["swoole"]["backlog"] = 128; // queue length
$config["swoole"]["log_file"] = "/tmp/test_swoole";
$config["swoole"]["heartbeat_check_interval"] = 20;
$config["swoole"]["heartbeat_idle_time"] = 60;
$config["swoole"]["open_eof_check"] = true;
$config["swoole"]["open_eof_split"] = true;
$config["swoole"]["package_eof"] = "\r\n";
$config["swoole"]["tcp_defer_accept"] = 5;
$config["swoole"]["discard_timeout_request"] = true;
$config["swoole"]["enable_reuse_port"] = true;

/**
 * log config, you can check ci framework to get detail info:
 * 0 = Disables logging, Error logging TURNED OFF
 * 1 = Error Messages (including PHP errors)
 * 2 = Debug Messages
 * 3 = Informational Messages
 * 4 = All Messages
 */
$config['log_threshold'] = 4;
$config['log_path'] = '/var/log/swoole/';
$config['log_file_extension'] = 'log';
$config['log_file_permissions'] = 0644;
$config['log_date_format'] = 'Y-m-d H:i:s';
