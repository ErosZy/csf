# CSF v0.11

> a tcp server framework base on swoole

CSF是一个参考了Codeigniter后基于swoole而编写的tcp框架，她定义了一套数据流规范，使得开发tcp服务像http服务一样简单

---

###1. 基于AACM的数据流

为了让开发tcp像一般的http服务一样简单，CSF参考了轻量级MVC框架Codeigniter并结合自身的需求规定了一套AACM（Analysis --> Action --> Controller --> Model)的数据流，其具体含义为：

* Analysis: 采用类似中间件的方式进行数据的解析操作
* Action: 对数据进行简单的处理，并分发给一个或多个Controller进行处理
* Controller: 控制层，作为业务相关逻辑的处理，与MVC中的Controller概念一致
* Model: 模型层，与MVC中的Model概念一致

###2. 准备使用

在一般的tcp服务开发过程中，我们需要像编写http一样与前端确定接口，这些接口在tcp服务中我们称为协议，协议一般包含标识码与数据，例如，我们为了简便我们可以定义一个这样的协议：

```shell
标志号*用户ID*JSON数据
```
例如：
```shell
10001*1*{"data":{}}
```

确定下协议格式后我们就可以进行tcp服务的开发了

###3. Analysis层

Analysis的作用主要是解析数据协议，其代码如下：

```PHP
<?php
    class DefaultAnalysis extends CoreAnalysis
    {
        // 必须实现process方法
        public function process($data, &$stop)
        {
            $stop = true;
            return [
                "router" => 10001, //标志号，唯一区分
                "data" => $data, //其余数据
            ];
        }
    }
?>
```

编写完成后你需要在application/config/router.php里面注册这个解析类

```PHP
<?php
    $config["analysis_routes"] = [
        "DefaultAnalysis"
    ];
?>
```
analysis_routes允许注册多个解析类，CSF会按照你的注册循序进行调用，若需要在某个调用后停止后续解析类的解析，只需将process方法里的$stop设置为true即可

###4. Action层

Action的作用主要是获取Analysis解析的最终数据，并做简单处理后分发给一个或多个Controller，其代码如下：

```PHP
<?php
class DefaultAction extends CoreAction
{
    private $_actions = [
        "receives/Welcome"
    ];

    public function __construct()
    {
        foreach ($this->_actions as $val) {
            $this->addTarget($val);
        }
    }

    public function distribute(Array $params)
    {
        foreach ($this->_actions as $val) {
            $this->setParams($val, $params);
        }

        $this->pub();
    }
}
?>
```
同样的，action需要在application/config/router.php中的进行注册，格式为按照标志号=>调用类名的方式进行注册, 如下所示：

```PHP
<?php
    $config["receive_routes"] = [
        10001 => "DefaultAction"
    ]
?>
```

###5. Controller层

Controller层与传统的MVC中的Controller相同，用于业务逻辑的编写，一个Controller的代码大抵如下：
```PHP
<?php

class Welcome extends CoreController
{
    private $serv = null;
    private $fd = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('DefaultModel', 'defaultModel');
        $this->load->library('Mcurl', 'mcurl');
    }

    public function process(Array $params)
    {
        $this->serv = $params["serv"];
        $this->fd = $params["fd"];

        // model load
        $this->defaultModel->sayHello();

        // library load
        $this->mcurl->isEnable();

        $this->serv->send($this->fd, "success\r\n");
    }
}
```

由于CSF参考了Codeigniter的实现，因此集成了Codeigniter的一些加载的常用方法：

1. $this->load->library($name,$nickname,$params): $name:加载的库路径，$nickname:使用时的别名, $params:构造函数参（你可以参考CI文档library部分）
2. $this->load->model($name,$nickname): $name: 模型路径（你可以参考CI文档model部分）
3. $this->load->helper($name): $name: helper路径（你可以参考CI文档helper部分）

除此之外，CSF也集成了swoole的task和taskawait方法创建了asyncTask和syncTask方法，其使用如下：

```PHP
<?php
class Welcome extends CoreController
{
    private $serv = null;
    private $fd = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function process($data)
    {
        // 异步任务
        $this->serv->task([
            "data" => "async task",
            "controller" => "AsyncTask",
            "method" => "process"
        ]);

        // 同步任务
        $this->serv->taskwait([
            "data" => "sync task",
            "controller" => "SyncTask",
            "method" => "process"
        ]);

        $this->serv->send($this->fd,"success");
    }
}
?>
```
关于task和taskawait的相关内容可以参考swoole的文档

###6. Model层

Model层与传统的MVC中的Model相同，用来抽象模型，一个Model的实现大致如下：

```PHP
<?php
    class UserModel extends CoreModel
    {
        protected $_tableName = "tablexxx";

        public function __construct()
        {
            parent::__construct();
        }

        public function findTokenById($id)
        {
            //something...
        }
    }
?>
```

具体的使用与Codeigniter一致，你可以参考CI文档Model的相关内容，但值得注意的是，Model也提供了load等相关方法，相关细节可以参考system/CoreModel和CoreController的实现

###7. Composer与Library

CSF会自动加载composer，大部分CSF存在的library都只是composer相关库的wrapper而已，例如：

```PHP
<?php
    class Database
    {
        protected $_connection = null;
        protected $_db = null;

        public function __construct()
        {
            $database = loadConfig("database", "database");
            $dsn = "mysql:host=" . $database["host"] . ";dbname=" . $database["dbname"];
            $user = $database["user"];
            $password = $database["password"];
            $this->_connection = new Nette\Database\Connection($dsn, $user, $password, ["lazy" => true]);
            $cacheMemoryStorage = new Nette\Caching\Storages\MemoryStorage;
            $structure = new Nette\Database\Structure($this->_connection, $cacheMemoryStorage);
            $conventions = new Nette\Database\Conventions\DiscoveredConventions($structure);
            $this->_db = new Nette\Database\Context($this->_connection, $structure, $conventions, $cacheMemoryStorage);
        }

        public function __destruct()
        {
            CoreHelper::logMessage('info','database destruct...');
            $this->_connection->disconnect();
        }

        public function __call($method, $args)
        {
            $callable = array($this->_db, $method);
            return call_user_func_array($callable, $args);
        }
    }
?>
```

若需要关闭自动的composer，你可以在config/config.php中找到相关配置进行关闭

###8. 连接池的使用
CSF自身不支持连接池，但推荐使用 https://github.com/swoole/php-cp

###9. 压力测试
benchmark里面存放了压力测试相关的代码，你可以通过阅读并修改config.php相关数据后启动php run.php执行压力测试，测试结果如下：

```shell
#1核1G机器上 10worker 10task 上线操作压测
concurrency:   	10000
request num:   	50000
lost num:      	0
success num:   	50000
total time:    	132.0
req per second:	378
one req use(ms): 2.641
```