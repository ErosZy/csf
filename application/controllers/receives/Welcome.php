<?php

class Welcome extends CoreController
{
    private $serv = null;
    private $fd = null;

    public function __construct()
    {
        parent::__construct();
//        $this->load->model('DefaultModel', 'defaultModel');
//        $this->load->library('Mcurl', 'mcurl');
    }

    public function process(Array $params)
    {
        $this->serv = $params["serv"];
        $this->fd = $params["fd"];

//        // model load
//        $this->defaultModel->sayHello();
//
//        // library load
//        $this->mcurl->isEnable();
//
//        // 异步任务
//        $this->serv->task([
//            "data" => "async task",
//            "controller" => "AsyncTask",
//            "method" => "process"
//        ]);
//
//        // 同步任务
//        $this->serv->taskwait([
//            "data" => "sync task",
//            "controller" => "SyncTask",
//            "method" => "process"
//        ]);

        $this->serv->send($this->fd, "success\r\n");
    }
}
