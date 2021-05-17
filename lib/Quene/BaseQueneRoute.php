<?php

namespace Mmx\Quene;

use Mmx\Core\Instance;

abstract class BaseQueneRoute extends Instance
{
    /**
     * 交换机名称
     * @var string
     */
    protected $exchange_name;

    final public function getExchangeName()
    {
        return (string)$this->exchange_name;
    }

    /**
     * 交换机类型
     * @var string
     */
    protected $exchange_type;

    final public function getExchangeType()
    {
        return (string)$this->exchange_type;
    }

    /**
     * 队列名称
     * @var string
     */
    protected $quene_name;

    final public function getQueneName()
    {
        return (string)$this->quene_name;
    }

    /**
     * 路由键
     * @var string
     */
    protected $route_key = '';

    final public function getRouteKey()
    {
        return (string)$this->route_key;
    }

    /**
     * 每次执行消费的间隔时间 大于0时是非阻塞式调用 等于0是是阻塞式调用
     * @var float
     */
    protected $time_interval = 0.1;

    final public function getTimeInterval()
    {
        return (float)$this->time_interval;
    }

    /**
     * 消息是否持久化
     * @var bool
     */
    protected $durable = false;

    final public function getDurable()
    {
        return (bool)$this->durable;
    }

    /**
     * @var string
     */
    protected $logPath = 'log';

    final public function getLogPath()
    {
        return (string)$this->logPath;
    }

//    abstract function consume(\AMQPEnvelope $env,\AMQPQueue $quene);

    /**
     * 具体的消费方法，由业务端自己实现
     * @param string $message 如果传入的消息是字符串，则原样返回，否则返回json_encode后的字符串
     * @return bool 如果返回true，则自动ack，如果返回false 则
     */
    abstract function consume(string $message): bool;

    /**
     * 投递消息
     * @param $message
     * @return array
     */
    public function publish($message)
    {
        $route = call_user_func([get_called_class(), 'instance']);
        return BaseRabbitmq::instance()->publish($route, $message);
    }

    /**
     * 处理成功之后的回调
     * @param string $message
     */
    public function onSuccess(string $message)
    {

    }

    /**
     * 处理异常后的回调
     * @param \Exception $exception
     * @param string $message
     */
    public function onError(\Exception $exception, string $message)
    {

    }
}
