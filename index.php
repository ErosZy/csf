<?php

define("ENVIRONMENT", "development");

switch (ENVIRONMENT) {
    case "development":
        error_reporting(-1);
        ini_set("display_errors", 1);
        break;
    case "testing":
    case "production":
        ini_set("display_errors", 0);
        if (version_compare(PHP_VERSION, "5.3", ">=")) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }
        break;
    default:
        die("The application environment is not set correctly.");
}

$systemPath = "system";
$appFolder = "application";

if (defined('STDIN')) {
    chdir(dirname(__FILE__));
}

if (($_temp = realpath($systemPath)) !== FALSE) {
    $systemPath = $_temp . "/";
} else {
    $systemPath = rtrim($systemPath, "/") . "/";
}

if (!is_dir($systemPath)) {
    die("Your system folder path does not appear to be set correctly. Please open the following file and correct this: " . pathinfo(__FILE__, PATHINFO_BASENAME));
}

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define("BASEPATH", str_replace("\\", "/", $systemPath));

if (is_dir($appFolder)) {
    if (($_temp = realpath($appFolder)) !== FALSE) {
        $appFolder = $_temp;
    }
    define("APPPATH", $appFolder . DIRECTORY_SEPARATOR);
} else {
    if (!is_dir(BASEPATH . $appFolder . DIRECTORY_SEPARATOR)) {
        die("Your application folder path does not appear to be set correctly. Please open the following file and correct this: " . SELF);
    }
    define("APPPATH", BASEPATH . $appFolder . DIRECTORY_SEPARATOR);
}

require_once BASEPATH . 'CoreServer.php';
CoreServer::run();