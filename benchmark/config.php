<?php

$config = [
    "process_num" => 1,
    "request_num" => 1,
    "server_url" => "localhost:9001",
    "test_function" => "long_tcp",
    "package_eof" => "#",
    "send_data" => [
        [
            "data" => '10001*1*{"token":"xxx"}',
            "num" => 1
        ]
    ]
];
