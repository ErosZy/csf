<?php

class SyncTask extends CoreController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('DefaultModel', 'defaultModel');
        $this->load->library('Mcurl', 'mcurl');
    }

    public function process(swoole_server $serv, $taskId, $fromId, $data)
    {
        // model load
        $this->defaultModel->sayHello();

        // library load
        $this->mcurl->isEnable();
        
        $serv->finish('sync ok!');
    }
}