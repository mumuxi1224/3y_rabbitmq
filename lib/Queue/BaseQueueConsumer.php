<?php
declare(strict_types=1);
namespace Mmx\Queue;

use Mmx\Core\Tool;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;
use Workerman\Stomp\Client;

class BaseQueueConsumer extends Worker
{
    /**
     * @var BaseQueueRoute[]
     */
    protected static $_router = [];

    /**
     * @var string
     */
    public $_log_path = null;

    /**
     * @var BaseRabbitmq
     */
    protected $client = null;

    /**
     * @var Client
     */
    protected $stompClient = null;

    /**
     * 配置信息
     * @var array
     */
    protected $_rabbitMqConfig = [];

//    /**
//     * 最大重试次数|设置为0的时候表示一直nack
//     * @var int
//     */
//    protected $_retryNum = 5;

    /**
     * 缓冲区大小 单位：M
     * @var int
     */
    protected $_maxSendBufferSize = 2;

    /**
     * @var int stomp的qos
     */
    protected $_prefetch_count = 1000;
//    /**
//     * 重试次数记录
//     * @var array
//     */
//    protected $_retryInfo = [];

//    /**
//     * 达到重试次数后重新加入队列处理
//     * @var array
//     */
//    protected $_retryQuene = [];

//    /**
//     * 重试队列是否正在运行中 防止重复运行
//     * @var bool
//     */
//    protected $_retryRunning = false;
    /**
     * 定时器id
     * @var array
     */
    protected $_timer_ids = [];

    /**
     * @throws \AMQPConnectionException
     */
    public function __construct(array $rabbitMq = [])
    {
        parent::__construct();
        $this->_rabbitMqConfig = $rabbitMq;
//        if (isset($rabbitMq['retry_num'])) {
//            $this->_retryNum = $rabbitMq['retry_num'];
//        }
        $this->_checkConnection($rabbitMq);
        //设置缓冲区
        if (isset($rabbitMq['maxSendBufferSize']) && is_numeric($rabbitMq['maxSendBufferSize']) && $rabbitMq['maxSendBufferSize'] > 0){
            $this->_maxSendBufferSize = $rabbitMq['maxSendBufferSize'];
        }
        TcpConnection::$defaultMaxSendBufferSize = $this->_maxSendBufferSize*1024*1024;
        //设置qos
        if (isset($rabbitMq['prefetch_count']) && is_numeric($rabbitMq['prefetch_count']) && $rabbitMq['prefetch_count'] > 0){
            $this->_prefetch_count = $rabbitMq['prefetch_count'];
        }
    }

    /**
     * 检查连接
     * @param array $config
     */
    protected function _checkConnection(array $config)
    {
        static::safeEcho(" > <w>Connection checking ...</w> \r\n");
        try {
            $this->client = BaseRabbitmq::instance();
            if (!$this->client->getConnection($config)->isConnected()) {
                $this->_exit('', 'Queue Server Connection Failed');
            }
            //不关闭连接 如果后面的队列有需要重新投递消息的可以直接调用基类publish（）方法
//            $this->client->closeConnection();
        } catch (\Exception $exception) {
            $this->_exit('Queue Server Connection Failed : ' . $exception->getMessage());
        }
        static::safeEcho(" > <w>Connection succeeded ...</w> \r\n");
    }

    /**
     * 单个队列服务注册 留待workman启动后执行
     * @param string $className
     */
    public function register(string $className)
    {
        //是否继承了基础BaseQueueRouter类
        if (!is_subclass_of($className, BaseQueueRoute::class)) {
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
            $this->_exit($className . ':empty queue name');
        }
        self::$_router[$className] = $router;
    }

    /**
     * 批量注册
     * @param array $classNameArray
     */
    public function registerMulti(array $classNameArray)
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
    protected function _log($log, $tag, string $module = 'consume')
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
        $stomp                        = $this->_rabbitMqConfig['stomp'] ?? '127.0.0.1:61613';
        $this->stompClient            = new Client('stomp://' . $stomp, array(
            'login'    => $this->_rabbitMqConfig['username'],
            'passcode' => $this->_rabbitMqConfig['password'],
            'vhost'    => $this->_rabbitMqConfig['vhost'],
            'debug'    => $this->_rabbitMqConfig['debug'] ?? false,
            'ack'      => 'client',
        ));

        $this->stompClient->onConnect = function (Client $client) {
            // 订阅
            foreach (self::$_router as $className => $object) {
                $client->subscribe($object->getQueneName(), function (Client $client, $data) use ($object) {
                    try {
                        var_dump($data['body']);
                        //存储上一条消息
//                        call_user_func([$object, 'setLastMsg'], $data);
                        //消费调用
                        call_user_func([$object, 'consume'], $data['body']);
                        //
                        $hasAck = call_user_func([$object, 'getAck']);
                        if (null === $hasAck || true === $hasAck) {
                            $this->_ack($data['headers']['destination'], $data['headers']['message-id']);
                        } else {
                            $this->_nack($data['headers']['destination'], $data['headers']['message-id']);
                        }
                    } catch (\Exception $exception) {
                        call_user_func([$object, 'onException'], $data['body'], $exception);
                        $this->_log($exception, 'retryException');
                    }
                    //消息需要ack  设置qos
                }, ['ack' => 'client','prefetch-count'=>$this->_prefetch_count]);
            }
        };
        $this->stompClient->onError   = function ($e) {
            $this->_log($e, 'onError');
        };
        $this->stompClient->connect();
    }

    /**
     * ack
     * @param string $destination
     * @param string $messageId
     */
    protected function _ack(string $destination, string $messageId)
    {
        try {
            $this->stompClient->ack($destination, $messageId);
        } catch (\Exception $exception) {
            $this->_log($exception, 'ACK', $destination . '_ack');
        }
    }

    /**
     * nack
     * @param string $destination
     * @param string $messageId
     */
    protected function _nack(string $destination, string $messageId)
    {
        try {
            $this->stompClient->nack($destination, $messageId);
        } catch (\Exception $exception) {
            $this->_log($exception, 'NACK', $destination . '_nack');
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
        //是否有未处理的重试队列
//        if(!empty($this->_retryQuene)){
//            foreach ($this->_retryQuene as $v){
//                $this->stompClient->send($v['quene_name'], $v['data']['body']);
//            }
//            static::safeEcho(" ----------------------- retryQuene ".count($this->_retryQuene)." ----------------------------- \r\n");
//        }
        //关闭连接
        if ($this->client && $this->client instanceof BaseRabbitmq) {
            $this->client->closeConnection();
        }
        if ($this->stompClient && $this->stompClient instanceof Client) {
            $this->stompClient->close();
        }
        static::safeEcho(" ----------------------- work end ----------------------------- \r\n");
    }
}
