<?php

$config = [
    "process_num" => 10000,
    "request_num" => 50000,
    "server_url" => "localhost:8083",
    "test_function" => "long_tcp",
    "package_eof" => "\r\n",
    "send_data" => [
        [
            "data" => '10001*test',
            "num" => 1
        ],[
            "data" => '10001*test',
            "num" => 1
        ],[
            "data" => '10001*test',
            "num" => 1
        ],[
            "data" => '10001*test',
            "num" => 1
        ],[
            "data" => '10001*test',
            "num" => 1
        ],
    ]
];