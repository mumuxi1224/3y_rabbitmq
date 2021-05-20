<?php

namespace Mmx\Quene;

use Mmx\Core\Instance;

class BaseRabbitmq extends Instance
{
    /**
     * rabbitmq链接
     * @var \AMQPConnection
     */
    protected $_connection = null;
    /**
     * @var \AMQPChannel
     */
    protected $_channel = null;
    protected $_channel_id;

    /**
     * @var \AMQPExchange
     */
    protected $_exchanges = [];

    /**
     * @var \AMQPQueue
     */
    protected $_queue = [];

    /**
     * @var \Exception
     */
    protected $_exception = null;

    /**
     * 创建|获取rabbitmq链接
     * @param array $config
     * @param float|float $timeout
     * @return \AMQPConnection
     * @throws \AMQPConnectionException
     */
    public function getConnection(array $config = [], float $timeout = 3.0)
    {
        if (empty($this->_connection) || !$this->_connection instanceof \AMQPConnection) {
            if (!isset($config['host'])) $config['host'] = '';
            if (!isset($config['vhost'])) $config['vhost'] = '/';
            if (!isset($config['port'])) $config['port'] = '';
            if (!isset($config['username'])) $config['username'] = '';
            if (!isset($config['password'])) $config['password'] = '';
            $this->_connection = new \AMQPConnection([
                'host'            => $config['host'],
                'virtual'         => $config['vhost'],
                'port'            => $config['port'],
                'login'           => $config['username'],
                'password'        => $config['password'],
                'connect_timeout' => $timeout
            ]);
            $this->_connection->connect();
        }
        return $this->_connection;
    }

    /**
     * 创建|获取通道
     * @param int|int $count
     * @return \AMQPChannel
     * @throws \AMQPConnectionException
     */
    public function getChannel(int $count = 1)
    {
        if (!$this->_channel instanceof \AMQPChannel) {
            $this->_channel = new \AMQPChannel($this->_connection);
            $this->_channel->qos(null, $count);
            $this->_channel_id = $this->_channel->getChannelId();
        }
        return $this->_channel;
    }

    /**
     * 创建|获取交换机
     * @param string $model_name
     * @param string $name
     * @param string $type
     * @param bool $declare
     * @return mixed
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function getExchange(string $model_name, string $name, string $type, bool $declare)
    {
        if (empty($this->_exchanges[$model_name]) || !$this->_exchanges[$model_name] instanceof \AMQPExchange) {
            $this->_exchanges[$model_name] = new \AMQPExchange($this->_channel);
        }
        //设置交换机名称
        $this->_exchanges[$model_name]->setName($name);
        //设置类型
        $this->_exchanges[$model_name]->setType($type);
        //是否持久化
        if ($declare) {
            $this->_exchanges[$model_name]->setFlags(AMQP_DURABLE);
        }
        $this->_exchanges[$model_name]->declareExchange();
        return $this->_exchanges[$model_name];
    }

    /**
     * 创建|获取队列
     * @param string $model_name
     * @param string|null $name
     * @param bool $declare
     * @param string $exchange_name
     * @param string $route_key
     * @return \AMQPQueue
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function getQueue(string $model_name, string $name, bool $declare, string $exchange_name, string $route_key)
    {
        if (empty($this->_queue[$model_name]) || !$this->_queue[$model_name] instanceof \AMQPQueue) {
            $this->_queue[$model_name] = new \AMQPQueue($this->_channel);
        }
        $this->_queue[$model_name]->setName($name);
        if ($declare) {
            $this->_queue[$model_name]->setFlags(AMQP_DURABLE);
        }
        $this->_queue[$model_name]->declareQueue();
        $this->_queue[$model_name]->bind($exchange_name, $route_key);

        return $this->_queue[$model_name];
    }

    /**
     * 创建
     * @param string $model_name
     * @param BaseQueneRoute $queneRoute
     * @return bool|mixed
     */
    public function createQueue(string $model_name, BaseQueneRoute $queneRoute)
    {
        try {
            $this->getConnection();
            $this->getChannel();
            $this->getExchange($model_name, $queneRoute->getExchangeName(), $queneRoute->getExchangeType(), $queneRoute->getDurable());
            $this->getQueue($model_name, $queneRoute->getQueneName(), $queneRoute->getDurable(), $queneRoute->getExchangeName(), $queneRoute->getRouteKey());
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            var_dump($exception->getLine());
            var_dump($exception->getFile());
            $this->_exception = $exception;
            return false;
        }
        $this->_exception = null;
        return $this->_queue[$model_name];
    }


    /**
     * 发布消息
     * @param BaseQueneRoute $queneRoute
     * @param $message
     * @param bool $close_connect
     * @return array
     */
    public function publish(BaseQueneRoute $queneRoute, $message, $close_connect = false)
    {
        try {
            $model_name = get_class($queneRoute);
            $this->createQueue($model_name, $queneRoute);
            $this->_exchanges[$model_name]->publish(
                $this->formatData($message),
                $queneRoute->getRouteKey(),
                AMQP_NOPARAM,
                [
                    'content_type'  => 'text/plain',    //协议
                    'delivery_mode' => $queneRoute->getDurable() ? 2 : 1, //是否持久化
                ]
            );
            if ($close_connect) {
                $this->closeConnection();
            }
        } catch (\Exception $exception) {
            return [false, $exception->getMessage() . ':' . $exception->getCode()];
        }
        return [true, null];
    }

    /**
     * 关闭connection
     * @return bool
     */
    public function closeConnection()
    {
        if ($this->_connection instanceof \AMQPConnection) {
            $this->_connection->disconnect();
            $this->_connection = null;
            return true;
        }
        return false;
    }

    /**
     * 统一格式化生产数据
     * @param $data
     * @return false|string
     */
    protected function formatData($data)
    {
        if (is_array($data)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        return (string)$data;
    }
}