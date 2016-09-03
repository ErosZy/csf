<?php

class DefaultAnalysis extends CoreAnalysis
{
    public function process($data, &$stop)
    {
        $stop = true;
        return [
            "data" => $data,
            "router" => 10001,
        ];
    }
}