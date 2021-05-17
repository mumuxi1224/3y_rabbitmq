<?php

namespace Mmx\Quene;

use Mmx\Core\Tool;
use Workerman\Timer;
use Workerman\Worker;

class BaseQueneConsumer extends Worker
{
    /**
     * @var array
     */
    protected static $_router = [];

    /**
     * @var \AMQPQueue
     */
    protected $_queue;

    /**
     * @var \AMQPConnection
     */
    protected $client = null;

    /**
     * 定时器id
     * @var array
     */
    protected $_timer_ids = [];

    public function __construct(array $rabbitMq = [])
    {
        parent::__construct();
        $this->_checkConnection($rabbitMq);
    }

    /**
     * 检查连接
     * @param array $config
     * @throws \AMQPConnectionException
     */
    protected function _checkConnection(array $config)
    {
        static::safeEcho(" > <w>Connection checking ...</w> \r\n");
        try {
            $this->client = BaseRabbitmq::instance();
            if (!$this->client->getConnection($config)->isConnected()) {
                $this->_exit('', 'Queue Server Connection Failed');
            }
        } catch (\Exception $exception) {
            $this->_exit('Queue Server Connection Failed : ' . $exception->getMessage(), $exception->getCode());
        }
        static::safeEcho(" > <w>Connection succeeded ...</w> \r\n");
    }

    /**
     * 单个队列服务注册 留待workman启动后执行
     * @param string $className
     */
    public function register(string $className)
    {
        //是否继承了基础BaseQueneRouter类
        if (!is_subclass_of($className, BaseQueneRoute::class)) {
            $this->_exit('subclass error');
        }
        //实例化类
        $router = call_user_func([$className, 'instance']);
        //检查必填参数
        if (empty($router->getExchangeName())) {
            $this->_exit($className . ':empty exchange name');
        }
        if (empty($router->getExchangeType())) {
            $this->_exit($className . ':empty exchange type');
        }
        if (empty($router->getQueneName())) {
            $this->_exit($className . ':empty quene name');
        }
        self::$_router[$className] = $router;
    }

    /**
     * 批量注册
     * @param array $classNameArray
     */
    public function registerMult(array $classNameArray)
    {
        foreach ($classNameArray as $v) {
            $this->register($v);
        }
    }

    /**
     * 退出
     * @param string $error
     * @param string $code
     */
    protected function _exit(string $error, string $code = '500')
    {
        static::safeEcho(" ----------------------- ERROR ----------------------------- \r\n");
        static::safeEcho(' > <w>message</w>:' . " {$error} \r\n");
        static::safeEcho(' > <w>code   </w>:' . " {$code} \r\n");
        static::safeEcho(" ----------------------- ERROR ----------------------------- \r\n");
        exit;
    }

    public function run()
    {
        $this->onWorkerStart = [$this, 'onWorkerStart'];
        $this->onWorkerStop  = [$this, 'onWorkerStop'];
        parent::run();
    }


    public function onWorkerStart(Worker $worker)
    {
        if (empty(self::$_router)) {
            $this->_exit('', 'empty router');
        }
        static::safeEcho(" ----------------------- work start ----------------------------- \r\n");
        $this->client = BaseRabbitmq::instance();
        //注册定时消费任务
        foreach (self::$_router as $rk => $rv) {
            $this->_queue[$rk]  = $this->client->createQueue($rk, $rv);
            $this->_timer_ids[] = Timer::add($rv->getTimeInterval(), function () use ($rk, $rv) {
                //get 非阻塞调用  consume 阻塞调用
                //get默认手动ack即AMQP_NOPARM 如果要自动ack则:AMQP_AUTOACK
                $env = $this->_queue[$rk]->get();
                if ($env) {
                    try {
                        //get 非阻塞调用  consume 阻塞调用
                        throw new \Exception('testErr');
                        $res = call_user_func([$rv, 'consume'], $env->getBody());
                        if ($res) {
                            //ack
                            $this->_queue[$rk]->ack($env->getDeliveryTag());
                        } else {
                            //nack
                            $this->_queue[$rk]->nack($env->getDeliveryTag());
                        }
                        call_user_func([$rv, 'onSuccess'], $env->getBody());
                    } catch (\Exception $exception) {
                        //抛出异常时 讲消息重新投递到队列中
                        $this->_queue[$rk]->nack($env->getDeliveryTag(), AMQP_REQUEUE);
                        call_user_func([$rv, 'onError'], $exception, $env->getBody());
                    }
                }
            });
        }
    }


    public function onWorkerStop(Worker $worker)
    {
        //清除定时器
        if ($this->_timer_ids) {
            foreach ($this->_timer_ids as $tv) {
                Timer::del($tv);
            }
        }
        //关闭连接
        if ($this->client && $this->client instanceof BaseRabbitmq) {
            $this->client->closeConnection();
        }
        static::safeEcho(" ----------------------- work end ----------------------------- \r\n");
    }
}
