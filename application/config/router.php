<?php
$config["connect_routes"] = [
    "DefaultConnect",
];

$config["analysis_routes"] = [
    "DefaultAnalysis"
];

$config["receive_routes"] = [
    10001 => "DefaultAction"
];

$config["process_routes"] = [
    "DefaultConsumer"
];


$config["close_routes"] = [
    "DefaultClose",
];