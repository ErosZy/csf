<?php

require_once BASEPATH . 'CoreHelper.php';

if (!function_exists('errorHandler')) {
    function errorHandler($severity, $message, $filepath, $line)
    {
        if (($severity & error_reporting()) !== $severity) {
            return;
        }

        $error = CoreHelper::loadClass('CoreException');
        $error->logException($severity, $message, $filepath, $line);
    }
}

if (!function_exists('exceptionHandler')) {
    function exceptionHandler($exception)
    {
        $error = CoreHelper::loadClass('CoreException');
        $error->logException('error', 'Exception: ' . $exception->getMessage(), $exception->getFile(), $exception->getLine());
    }
}

if (!function_exists('shutDownHandler')) {
    function shutDownHandler()
    {
        $lastError = error_get_last();
        if (isset($lastError) &&
            ($lastError['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING))
        ) {
            errorHandler($lastError['type'], $lastError['message'], $lastError['file'], $lastError['line']);
        }
    }
}