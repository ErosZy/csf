<?php

require_once BASEPATH . 'CoreHelper.php';

class CoreLog
{
    protected $_log_path;
    protected $_file_permissions = 0644;
    protected $_threshold = 1;
    protected $_threshold_array = [];
    protected $_date_fmt = 'Y-m-d H:i:s';
    protected $_file_ext;
    protected $_enabled = true;
    protected $_levels = [
        'ERROR' => 1,
        'DEBUG' => 2,
        'INFO' => 3,
        'ALL' => 4
    ];

    public function __construct()
    {
        $logPath = CoreHelper::loadConfig("log_path", "config");
        $logFileExtension = CoreHelper::loadConfig("log_file_extension", "config");
        $logThreshold = CoreHelper::loadConfig("log_threshold", "config");
        $logDateFormat = CoreHelper::loadConfig("log_date_format", "config");
        $logFilePermissions = CoreHelper::loadConfig("log_file_permissions", "config");

        $this->_log_path = ($logPath !== '') ? $logPath : APPPATH . 'logs/';
        $this->_file_ext = (isset($logFileExtension) && $logFileExtension !== '')
            ? ltrim($logFileExtension, '.') : 'php';

        file_exists($this->_log_path) || mkdir($this->_log_path, 0755, true);

        if (!is_dir($this->_log_path) || !CoreHelper::isWriteable($this->_log_path)) {
            $this->_enabled = false;
        }

        if (is_numeric($logThreshold)) {
            $this->_threshold = (int)$logThreshold;
        } elseif (is_array($logThreshold)) {
            $this->_threshold = 0;
            $this->_threshold_array = array_flip($logThreshold);
        }

        if (!empty($logDateFormat)) {
            $this->_date_fmt = $logDateFormat;
        }

        if (!empty($logFilePermissions) && is_int($logFilePermissions)) {
            $this->_file_permissions = $logFilePermissions;
        }
    }

    public function writeLog($level, $msg)
    {
        if ($this->_enabled === false) {
            return false;
        }

        $level = strtoupper($level);

        if ((!isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold))
            && !isset($this->_threshold_array[$this->_levels[$level]])
        ) {
            return false;
        }

        $filepath = $this->_log_path . 'log-' . date('Y-m-d') . '.' . $this->_file_ext;
        $message = '';

        if (!file_exists($filepath)) {
            $newfile = true;
            if ($this->_file_ext === 'php') {
                $message .= "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n";
            }
        }

        if (!$fp = @fopen($filepath, 'ab')) {
            return false;
        }

        if (strpos($this->_date_fmt, 'u') !== false) {
            $microtime_full = microtime(true);
            $microtime_short = sprintf("%06d", ($microtime_full - floor($microtime_full)) * 1000000);
            $date = new DateTime(date('Y-m-d H:i:s.' . $microtime_short, $microtime_full));
            $date = $date->format($this->_date_fmt);
        } else {
            $date = date($this->_date_fmt);
        }

        $message .= $level . ' - ' . $date . ' --> ' . $msg . "\n";

        flock($fp, LOCK_EX);

        for ($written = 0, $length = strlen($message); $written < $length; $written += $result) {
            if (($result = fwrite($fp, substr($message, $written))) === false) {
                break;
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        if (isset($newfile) && $newfile === true) {
            chmod($filepath, $this->_file_permissions);
        }

        return is_int($result);
    }
}