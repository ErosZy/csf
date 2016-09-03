<?php

$config = [];
require_once "./config.php";
require_once "./Benchmark.php";

$bc = new Benchmark($config["test_function"]);
$bc->process_num = $config["process_num"];
$bc->request_num = $config["request_num"];
$bc->server_url = $config["server_url"];
$bc->server_config = parse_url($config["server_url"]);
$send_data = $config["send_data"];
$package_eof = $config["package_eof"];

$bc->process_req_num = intval($bc->request_num / $bc->process_num);
$bc->run();
$bc->report();

function long_tcp(Benchmark $bc)
{
    global $send_data, $package_eof;
    static $client = null;
    static $i;
    static $index;
    $start = microtime(true);
    if (empty($client)) {
        $client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        $client->set(array('open_eof_check' => true, "package_eof" => $package_eof));
        $end = microtime(true);
        $conn_use = $end - $start;
        $bc->max_conn_time = $conn_use;
        $i = 0;
        $index = 0;

        if (!$client->connect($bc->server_config['host'], $bc->server_config['port'], 2)) {
            error:
            echo "Error: " . swoole_strerror($client->errCode) . "[{$client->errCode}]\n";
            $client = null;
            return false;
        }
        $start = $end;
    }

    $data = $send_data[$index]["data"] . $package_eof;
    if (!$client->send($data)) {
        goto error;
    }

    $end = microtime(true);
    $write_use = $end - $start;
    if ($write_use > $bc->max_write_time) $bc->max_write_time = $write_use;
    $start = $end;

    $i++;
    if ($i >= $send_data[$index]["num"]) {
        $index++;
    }

    $ret = $client->recv();

    if (empty($ret)) {
        echo $bc->pid, "#$i", " is lost\n";
        return false;
    }

    $end = microtime(true);
    $read_use = $end - $start;

    if ($read_use > $bc->max_read_time) {
        $bc->max_read_time = $read_use;
    }

    return true;
}