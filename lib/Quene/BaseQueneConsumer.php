<?php

namespace Mmx\Quene;

use Workerman\Timer;
use Workerman\Worker;

class BaseQueneConsumer extends Worker
{
    /**
     * @var array
     */
    protected static $_router = [];

    protected static $_exchanges = [];

    /**
     * 日志存储位置
     * @var string
     */
    public $_log_path = null;

    public function __construct($socket_name = '', array $context_option = array())
    {
        parent::__construct($socket_name, $context_option);
    }

    /**
     * 单个队列服务注册 留待workman启动后执行
     * @param string $className
     */
    public function register(string $className){
        //是否继承了基础BaseQueneRouter类
        if (!is_subclass_of($className,BaseQueneRoute::class)){
            $this->_exit('subclass error');
        }
        //实例化类
        $router =call_user_func([$className,'instance']);
        if (empty($router->getExchangeName())){
            $this->_exit($className,'empty exchange name');
        }
        if (empty($router->getExchangeType())){
            $this->_exit($className,'empty exchange type');
        }
        if (empty($router->getQueneName())){
            $this->_exit($className,'empty quene name');
        }
        self::$_router[ $className ] = $router;
    }

    /**
     * 批量注册
     * @param array $classNameArray
     */
    public function registerMult(array $classNameArray){
        foreach ($classNameArray as $v){
            $this->register($v);
        }
    }
    /**
     * 退出
     * @param string $error
     * @param string $code
     */
    protected function _exit(string $className = '',string $error, string $code = '500'){
        static::safeEcho(" ----------------------- ERROR ----------------------------- \r\n");
        static::safeEcho(' > <w>message</w>:' . " {$error} \r\n");
        static::safeEcho(' > <w>code   </w>:' . " {$code} \r\n");
        if($className){
            static::safeEcho(' > <w>route  </w>:' . " {$className} \r\n");
        }
        static::safeEcho(" ----------------------- ERROR ----------------------------- \r\n");
        exit;
    }

    public function run()
    {
        $this->onWorkerStart = [$this, 'onWorkerStart'];
        $this->onWorkerStop = [$this, 'onWorkerStop'];
        parent::run(); // TODO: Change the autogenerated stub
    }


    public function onWorkerStart(Worker $worker){
        if (empty(self::$_router)){
            $this->_exit('','empty router');
        }
        static::safeEcho(" ----------------------- work start ----------------------------- \r\n");
        // 每2.5秒执行一次
        $time_interval = 2.5;
        //注册定时消费任务
        foreach (self::$_router as $rv){
            Timer::add($time_interval, function ($rv){
                var_dump($rv);
            });
        }
    }

    public function onWorkerStop(Worker $worker){
        var_dump(2);
    }
}
