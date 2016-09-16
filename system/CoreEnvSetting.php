<?php

require_once BASEPATH . "CoreError.php";

set_error_handler("errorHandler");
set_exception_handler("exceptionHandler");
register_shutdown_function("shutdownHandler");

require_once BASEPATH . 'CoreHelper.php';

if ($composer = CoreHelper::loadConfig("composer_autoload", "config")) {
    if ($composer === true) {
        file_exists(APPPATH . "vendor/autoload.php")
            ? require_once(APPPATH . "vendor/autoload.php")
            : die("composer autoload is set to TRUE but " . APPPATH . "vendor/autoload.php was not found.");
    } elseif (file_exists($composer)) {
        require_once($composer);
    } else {
        die('Could not find the specified ".$config["composer_autoload"]." path: ' . $composer);
    }
}

$charset = strtoupper(CoreHelper::loadConfig("charset", "config"));
ini_set("default_charset", $charset);

if (extension_loaded("mbstring")) {
    define("MB_ENABLED", TRUE);
    @ini_set("mbstring.internal_encoding", $charset);
    mb_substitute_character("none");
} else {
    define("MB_ENABLED", FALSE);
}

if (extension_loaded("iconv")) {
    define("ICONV_ENABLED", TRUE);
    @ini_set("iconv.internal_encoding", $charset);
} else {
    define("ICONV_ENABLED", FALSE);
}

if (CoreHelper::isPHP("5.6")) {
    ini_set("php.internal_encoding", $charset);
}

require_once BASEPATH . 'CoreController.php';

function &getInstance()
{
    $instance = CoreController::$instance;
    return $instance;
}


