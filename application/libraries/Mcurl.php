<?php

require_once BASEPATH . 'CoreHelper.php';

class Mcurl
{
    private $calls = array();
    private $curl_parent;

    function __construct()
    {
        CoreHelper::logMessage('debug', 'Mcurl Class Initialized');

        if (!$this->isEnable()) {
            CoreHelper::logMessage('error', 'Mcurl Class - PHP was not built with cURL enabled. Rebuild PHP with --with-curl to use cURL.');
        } else {
            $this->curl_parent = curl_multi_init();
        }
    }

    function isEnable()
    {
        return function_exists('curl_multi_init');
    }

    function addCall($key = null, $method, $url, $params = array(), $options = array())
    {
        if (is_null($key)) {
            $key = count($this->calls);
        }

        $url_match = '/https?:\/\//';
        preg_match($url_match, $url, $matches);
        if (!count($matches)) {
            $url = $_SERVER["SERVER_ADDR"] . "/" . $url;
        }

        $timeout = $options["CURLOPT_TIMEOUT"];
        $options["CURLOPT_TIMEOUT"] = empty($timeout) ? 0.5 : $timeout;

        // check to see if the multi handle has been closed

        // init the multi handle again

        $resource_type = get_resource_type($this->curl_parent);

        if (!$resource_type || $resource_type == 'Unknown') {
            $this->calls = array();
            $this->curl_parent = curl_multi_init();
        }

        $this->calls [$key] = array(
            "method" => $method,
            "url" => $url,
            "params" => $params,
            "options" => $options,
            "curl" => null,
            "response" => null,
            "error" => null
        );

        $this->calls[$key]["curl"] = curl_init();

        // If its an array (instead of a query string) then format it correctly

        if (is_array($params)) {

            $params = http_build_query($params, NULL, '&');
        }

        $method = strtoupper($method);

        // only supports get/post requests

        // set some special curl opts for each type of request

        switch ($method) {

            case "POST":
                curl_setopt($this->calls[$key]["curl"], CURLOPT_URL, $url);
                curl_setopt($this->calls[$key]["curl"], CURLOPT_POST, TRUE);
                curl_setopt($this->calls[$key]["curl"], CURLOPT_POSTFIELDS, $params);
                break;

            case "GET":
                curl_setopt($this->calls[$key]["curl"], CURLOPT_URL, $url . "?" . $params);
                break;

            default:
                log_message('error', 'Mcurl Class - Provided http method is not supported. Only POST and GET are currently supported.');
                break;
        }

        curl_setopt($this->calls[$key]["curl"], CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->calls[$key]["curl"], CURLOPT_FOLLOWLOCATION, TRUE);

        curl_setopt_array($this->calls[$key]["curl"], $options);

        curl_multi_add_handle($this->curl_parent, $this->calls[$key]["curl"]);
    }

    // run the calls in the curl requests in $this->calls

    function execute()
    {
        if (count($this->calls)) {
            // kick off the requests

            do {

                $multi_exec_handle = curl_multi_exec($this->curl_parent, $active);
            } while ($active > 0);

            // after all requests finish, set errors and repsonses in $this->calls

            foreach ($this->calls as $key => $call) {

                $error = curl_error($this->calls[$key]["curl"]);
                if (!empty($error)) {
                    $this->calls[$key]["error"] = $error;
                }
                $this->calls[$key]["response"] = curl_multi_getcontent($this->calls[$key]["curl"]);
                curl_multi_remove_handle($this->curl_parent, $this->calls[$key]["curl"]);
            }
            curl_multi_close($this->curl_parent);
        }

        return $this->calls;
    }

    function debug()
    {
        echo "<h2>mcurl debug</h2>";
        foreach ($this->calls as $call) {
            echo '<p>url: <b>' . $call["url"] . '</b></p>';
            if (!is_null($call["error"])) {
                echo '<p style="color:red;">error: <b>' . $call["error"] . '</b></p>';
            }
            echo '<textarea cols="100" rows="10">' . htmlentities($call["response"]) . "</textarea><hr>";
        }
    }

}