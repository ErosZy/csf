# CSF

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
        // 必须实现distribute方法
        public function distribute(Array $params)
        {
            // 1.对数据进行简单处理
            // do something...

            // 2.分发数据
            $this->addTarget("receives/Controller1",$params);
            $this->addTarget("receives/Controller2",$params);
            $this->addTarget("receives/Controller3",$params);
            $this->pub();
        }
    }
?>
```
同样的，action需要在application/config/router.php中的进行注册，格式为按照标志号=>调用类名的方式进行注册
,如下所示：

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

        public function __construct(Array $params)
        {
            parent::__construct($params);
            $this->serv = $params["serv"];
            $this->fd = $params["fd"];
            $this->process($params["data"]);
        }

        public function process($data)
        {
            $this->serv->send($this->fd,"success");
        }
    }
?>
```

由于CSF参考了Codeigniter的实现，因此集成了Codeigniter的一些加载的常用方法：

1. $this->load->library($name,$params,$nickname): $name:加载的库路径，$params:构造函数参数，$nickname:使用时的别名（你可以参考CI文档library部分）
2. $this->load->model($name): $name: 模型路径（你可以参考CI文档model部分）
3. $this->load->helper($name): $name: helper路径（你可以参考CI文档helper部分）

除此之外，CSF也集成了swoole的task和taskawait方法创建了asyncTask和syncTask方法，其使用如下：

```PHP
<?php
    class Welcome extends CoreController
    {
        private $serv = null;
        private $fd = null;

        public function __construct(Array $params)
        {
            parent::__construct($params);
            $this->serv = $params["serv"];
            $this->fd = $params["fd"];
            $this->process($params["data"]);
        }

        public function process($data)
        {
            /**
             * @desc 同步阻塞，等待task进程返回数据
             * @param $path 调用路径
             * @param $method 调用方法
             * @param $data 传入数据
             * @return string
             */
            $result = $this->syncTask("SyncTask","process","something");

            /**
             * @desc 异步执行
             * @param $path 调用路径
             * @param $method 调用方法
             * @param $data 传入数据
             * @return string
             */
            $this->async("AsyncTask","process","something...");

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
            logMessage('info','database destruct...');
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
CSF自身不支持连接池，但是基于library及syncTask我们可以从逻辑上编写一个连接池：

* 首先，当调用model时，不适用$this->load->model方法，反而使用$this->syncTask方法去调用DBPoolCaller

```PHP
<?php
    $result = $this->syncTask("DBPoolCaller", "process", [
        "model" => "UserModel",
        "method" => "findTokenById",
        "params" => $uid,
    ]);
?>
```

* 在controller/tasks下创建DBPoolCaller.php，其具体实现如下

```PHP
<?php

class DBPoolCaller extends CoreController
{
    private $serv = null;
    public function __construct(Array $params)
    {
        parent::__construct($params);
        $this->serv = $params["serv"];
    }


    public function process($data)
    {
        static $maps = [];

        $model = $data["model"];
        $method = $data["method"];
        $params = $data["params"];
        if (!$model || !$method || !$params) {
            $this->serv->finish(false);
        } else {
            $obj = $maps[$model];
            if (!$obj) {
                $this->load->model($model);
                $obj = $maps[$model] = $this->$model;
            }

            try {
                $results = $obj->$method($params);
            } catch (Exception $e) {
                $this->load = &loadClass("CoreLoader", null, null, false);
                $this->$model = null;
                $this->load->model($model);
                $this->$model->loadDb();
                $obj = $maps[$model] = $this->$model;
                $results = $obj->$method($params);
            }
        }

        $this->serv->finish(json_encode($results));
    }
}

```

* 在library中创建一个Db_pool.php，继承Database的__desturct方法（禁止关闭连接）

```PHP
<?php
    require_once APPPATH . "libraries/Database.php";

    class Db_pool extends Database
    {
        public function __construct()
        {
            parent::__construct();
        }

        public function __destruct()
        {
            //不调用父类自身的析构函数用来关闭连接
        }
    }
?>
```

* 撰写PoolModel.php

```PHP
<?php

class PoolModel extends CoreModel
{
    protected static $_pool = null;

    public function __construct()
    {
        parent::__construct();
        if (self::$_pool == null) {
            $this->loadDb();
        }
    }

    public function loadDb(){
        $CN = &getInstance();
        $CN->db = null;
        $this->load->library("Db_pool", null, "db");
        self::$_pool = $this->db;
    }
}
```

* 编写相关Model

```PHP
<?php
    require_once APPPATH . "models/PoolModel.php";

    class UserModel extends PoolModel
    {
        protected $_tableName = "xxxx";

        public function __construct()
        {
            parent::__construct();
        }

        public function findTokenById($id)
        {
            $querySQL = "xxxxxxxxxxxxxxxx";
            $res = self::$_pool->query($querySQL, $id);
            $row = $res->fetch();
            return (array)$row;
        }
    }
?>
```

###9. 压力测试
benchmark里面存放了压力测试相关的代码，你可以通过阅读并修改config.php相关数据后启动php run.php执行压力测试，测试结果如下：

```shell
#1核2G机器上 30worker 7task 上线操作压测
concurrency:    10000
request num:    50000
lost num:   0
success num:    50000
total time: 49.88
req per second: 1002
one req use(ms):    0.997
```

###10. 更多
CSF已经被用在了我们自己的线上并且性能还相当不错，其核心代码及配置都相当简单，若出现问题，你可以通过阅读system下面的源码及config的相关配置了解各个方法和参数的含义，当然由于水平有限，CSF肯定是不完善的，你可以通过pull request直接提交你的修改，你也可以通过zyeros1991@gmail.com联系我
