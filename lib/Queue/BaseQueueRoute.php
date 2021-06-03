<?php
declare(strict_types=1);

namespace Mmx\Queue;

use Mmx\Core\Instance;

abstract class BaseQueueRoute extends Instance
{
    /**
     * 交换机名称
     * @var string
     */
    protected $exchange_name;

    final public function getExchangeName(): string
    {
        return (string)$this->exchange_name;
    }

    /**
     * 交换机类型
     * @var string
     */
    protected $exchange_type = \AMQP_EX_TYPE_DIRECT;

    final public function getExchangeType(): string
    {
        return (string)$this->exchange_type;
    }

    /**
     * 队列名称
     * @var string
     */
    protected $queue_name;

    final public function getQueneName(): string
    {
        return (string)$this->queue_name;
    }

    /**
     * 路由键
     * @var string
     */
    protected $route_key = '';

    final public function getRouteKey(): string
    {
        return (string)$this->route_key;
    }

//    /**
//     * 消息达到重试次数后再次放回队列的时间
//     * @var int
//     */
//    protected $retry_time = 60;
//
//    final public function getRetryTime(): int
//    {
//        return (int)$this->retry_time;
//    }

    /**
     * 消息是否持久化
     * @var bool
     */
    protected $durable = true;

    final public function getDurable(): bool
    {
        return (bool)$this->durable;
    }

    /**
     * @var int
     */
    protected $qos = 10;

    final public function getQos(): int
    {
        return (int)$this->qos;
    }
    /**
     * 存储上一条消息
     * @var mixed
     */
    protected $lastMsg = null;

    final public function setLastMsg($msg)
    {
        $this->ack = null;
        return $this->lastMsg = $msg;
    }

    final public function getLastMag()
    {
        return $this->lastMsg;
    }

    /**
     * @var null
     */
    protected $ack = null;

    final public function setAck(bool $ack)
    {
        $this->ack = $ack;
    }

    final public function getAck()
    {
        return $this->ack;
    }

    /**
     * 具体的消费方法，由业务端自己实现
     * @param string $message 如果传入的消息是字符串，则原样返回，否则返回json_encode后的字符串
     */
    abstract function consume(string $message);

    /**
     * 投递消息
     * @param $message
     * @return array
     */
    public function publish($message): array
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
     * 发送错误时候的回调
     * @param string $message
     * @param \Exception $exception
     */
    public function onException(string $message, \Exception $exception)
    {

    }

    final public function ack()
    {
        $this->setAck(true);
    }

    public function nack()
    {
        $this->setAck(false);
    }
}
