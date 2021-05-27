<?php

namespace Mmx\Quene;

use Mmx\Core\Tool;
use Workerman\Timer;
use Workerman\Worker;
use Workerman\Stomp\Client;

class BaseQueneConsumer extends Worker
{
    /**
     * @var array
     */
    protected static $_router = [];

    /**
     * @var string
     */
    public $_log_path = null;

    /**
     * @var \AMQPConnection
     */
    protected $client = null;

    /**
     * @var \Workerman\Stomp\Client
     */
    protected $stompClient = null;

    /**
     * 配置信息
     * @var array
     */
    protected $_rabbitMqConfig = [];

    /**
     * 最大重试次数|设置为0的时候表示一直nack
     * @var int
     */
    protected $_retryNum = 5;

    /**
     * 重试次数记录
     * @var array
     */
    protected $_retryInfo = [];

    /**
     * 达到重试次数后重新加入队列处理
     * @var array
     */
    protected $_retryQuene = [];

    /**
     * 重试队列是否正在运行中 防止重复运行
     * @var bool
     */
    protected $_retryRunning = false;
    /**
     * 定时器id
     * @var array
     */
    protected $_timer_ids = [];

    public function __construct(array $rabbitMq = [])
    {
        parent::__construct();
        $this->_rabbitMqConfig = $rabbitMq;
        if (isset($rabbitMq['retry_num'])) {
            $this->_retryNum = $rabbitMq['retry_num'];
        }
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

    /**
     * 日志
     * @param $log
     * @param $tag
     * @param string $module
     */
    protected function _log($log, $tag, $module = 'consume')
    {
        if ($this->_log_path) {
            if ($log instanceof \Exception) {
                $log = "{$log->getCode()} : {$log->getMessage()}";
            }
            Tool::log($module, $log, $this->_log_path, $tag);
        }
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
//        $this->client = BaseRabbitmq::instance();
        $stomp                        = isset($this->_rabbitMqConfig['stomp']) ? $this->_rabbitMqConfig['stomp'] : '127.0.0.1:61613';
        $this->stompClient            = new Client('stomp://' . $stomp, array(
            'login'    => $this->_rabbitMqConfig['username'],
            'passcode' => $this->_rabbitMqConfig['password'],
            'vhost'    => $this->_rabbitMqConfig['vhost'],
            'debug'    => isset($this->_rabbitMqConfig['debug']) ? $this->_rabbitMqConfig['debug'] : false,
            'ack'      => 'client',
        ));
        $this->stompClient->onConnect = function (Client $client) {
            // 订阅
            foreach (self::$_router as $className => $object) {
                $client->subscribe($object->getQueneName(), function (Client $client, $data) use ($object) {
                    try {
                        //消费调用
                        $res = call_user_func([$object, 'consume'], $data['body']);
                        list($clientId,$session,) = explode('@@',$data['headers']['message-id']);
                        $retryKey = $clientId.$session.md5($data['body']);
                        if ($res) {
                            //删除可能存在的重试信息
                            if (isset($this->_retryInfo[ $data['headers']['message-id'] .md5($data['body']) ]))unset($this->_retryInfo[ $retryKey ]);
                            call_user_func([$object, 'onSuccess'], $data['body']);
                            $this->_ack($data['headers']['destination'], $data['headers']['message-id']);
                        } else {
                            //记录重试次数
                            if (isset($this->_retryInfo[ $retryKey ])){
                                $this->_retryInfo[ $retryKey ] ++;
                            }else{
                                $this->_retryInfo[ $retryKey ] = 1;
                            }
                            if ($this->_retryNum > 0 && $this->_retryInfo[ $retryKey ] >= $this->_retryNum) {
                                call_user_func([$object, 'onRetryError'], $data['body']);
                                //先ack
                                $this->_ack($data['headers']['destination'], $data['headers']['message-id']);
                                //删除记录
                                unset($this->_retryInfo[ $retryKey ]);
                                //丢回重试队列
                                $this->_retryQuene[] = [
                                    'retry_time'=>time() + $object->getRetryTime(),
                                    'data'=>$data,
                                    'quene_name'=>$object->getQueneName(),
                                ];
                            } else {
                                $this->_nack($data['headers']['destination'], $data['headers']['message-id']);
                            }
                        }
                    } catch (\Exception $exception) {
                        call_user_func([$object, 'onException'], $data['body'], $exception);
                        $this->_log($exception,'retryException');
                    }
                    //消息需要ack
                }, ['ack' => 'client']);
            }
            //重试队列
            Timer::add(1,function ()use($client){
                if ($this->_retryRunning)return;
                $this->_retryRunning = true;
                $now = time();
                if ($this->_retryQuene){
                    foreach ($this->_retryQuene as $k=>$v){
                        if ($v['retry_time'] <=$now){
                            $client->send($v['quene_name'], $v['data']['body']);
                            unset($this->_retryQuene[$k]);
                        }else{
                            //顺序时间加入的
                            break;
                        }
                    }
                }
                $this->_retryRunning = false;
            });
        };
        $this->stompClient->onError   = function ($e) {
            $this->_log($e, 'onError');
        };
        $this->stompClient->connect();
    }

    /**
     * ack
     * @param string $destination
     * @param string $message_id
     */
    protected function _ack(string $destination, string $message_id)
    {
        try {
            $this->stompClient->ack($destination, $message_id);
            throw new \Exception('test');
        } catch (\Exception $exception) {
            $this->_log($exception, 'ACK', $destination . '_ack');
        }
    }

    /**
     * nack
     * @param string $destination
     * @param string $message_id
     */
    protected function _nack(string $destination, string $message_id)
    {
        try {
            $this->stompClient->nack($destination, $message_id);
        } catch (\Exception $exception) {
            $this->_log($exception, 'NACK', $destination . '_nack');
        }
    }

//    public function onWorkerStart(Worker $worker)
//    {
//        if (empty(self::$_router)) {
//            $this->_exit('', 'empty router');
//        }
//        static::safeEcho(" ----------------------- work start ----------------------------- \r\n");
//        $this->client = BaseRabbitmq::instance();
//        //注册定时消费任务
//        foreach (self::$_router as $rk => $rv) {
//            $this->_queue[$rk]  = $this->client->createQueue($rk, $rv);
//            $this->_timer_ids[] = Timer::add($rv->getTimeInterval(), function () use ($rk, $rv) {
//                //get 非阻塞调用  consume 阻塞调用
//                //get默认手动ack即AMQP_NOPARM 如果要自动ack则:AMQP_AUTOACK
//                $env = $this->_queue[$rk]->get();
//                if ($env) {
//                    try {
//                        //get 非阻塞调用  consume 阻塞调用
//                        $res = call_user_func([$rv, 'consume'], $env->getBody());
//                        if ($res) {
//                            //ack
//                            $this->_queue[$rk]->ack($env->getDeliveryTag());
//                        } else {
//                            //nack
//                            $this->_queue[$rk]->nack($env->getDeliveryTag());
//                        }
//                        call_user_func([$rv, 'onSuccess'], $env->getBody());
//                    } catch (\Exception $exception) {
//                        //抛出异常时 讲消息重新投递到队列中
//                        $this->_queue[$rk]->nack($env->getDeliveryTag(), AMQP_REQUEUE);
//                        call_user_func([$rv, 'onError'], $exception, $env->getBody());
//                    }
//                }
//            });
//        }
//    }


    public function onWorkerStop(Worker $worker)
    {
        //清除定时器
        if ($this->_timer_ids) {
            foreach ($this->_timer_ids as $tv) {
                Timer::del($tv);
            }
        }
        //是否有未处理的重试队列
        if(!empty($this->_retryQuene)){
            foreach ($this->_retryQuene as $v){
                $this->stompClient->send($v['quene_name'], $v['data']['body']);
            }
            static::safeEcho(" ----------------------- retryQuene ".count($this->_retryQuene)." ----------------------------- \r\n");
        }
        //关闭连接
        if ($this->client && $this->client instanceof BaseRabbitmq) {
            $this->client->closeConnection();
        }
        if ($this->stompClient && $this->stompClient instanceof Client){
            $this->stompClient->close();
        }
        static::safeEcho(" ----------------------- work end ----------------------------- \r\n");
    }
}
