<?php
declare(strict_types=1);

namespace Mmx\Queue;

use Mmx\Core\Instance;

class BaseRabbitmq extends Instance
{
    /**
     * @var \AMQPConnection
     */
    protected $_connection = null;

    /**
     * @var \AMQPChannel
     */
    protected $_channel = null;

    /**
     * @var int
     */
    protected $_channel_id;

    /**
     * @var \AMQPExchange[]
     */
    protected $_exchanges = [];

    /**
     * @var \AMQPQueue[]
     */
    protected $_queue = [];

    /**
     * @var \Exception
     */
    protected $_exception = null;

    /**
     * 创建|获取rabbitmq链接
     * @param array $config
     * @param float $timeout
     * @return \AMQPConnection
     * @throws \AMQPConnectionException
     */
    public function getConnection(array $config = [], float $timeout = 3.0): \AMQPConnection
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
     * 静态的连接rabbitmq,为了方便直接publish
     * @param array $config
     * @param float $timeout
     * @return mixed
     */
    public static function Connection(array $config = [], float $timeout = 3.0)
    {
        return call_user_func([self::instance(), 'getConnection'], $config, $timeout);
    }

    /**
     * 创建|获取通道
     * @param int $count
     * @return \AMQPChannel
     * @throws \AMQPConnectionException
     */
    public function getChannel(int $count = 10, $global = false): \AMQPChannel
    {
        if (!$this->_channel instanceof \AMQPChannel) {
            $this->_channel = new \AMQPChannel($this->_connection);
            $this->_channel->qos(0, $count, $global);
            $this->_channel_id = $this->_channel->getChannelId();
        }
        return $this->_channel;
    }

    /**
     * 创建|获取交换机
     * @param string $modelName 子类的类名
     * @param string $name 交换机名称
     * @param string $type 交换机类型
     * @param bool $declare
     * @return \AMQPExchange
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException|\AMQPChannelException
     */
    public function getExchange(string $modelName, string $name, string $type, bool $declare): \AMQPExchange
    {
        if (empty($this->_exchanges[$modelName]) || !$this->_exchanges[$modelName] instanceof \AMQPExchange) {
            $this->_exchanges[$modelName] = new \AMQPExchange($this->_channel);
            //设置交换机名称
            $this->_exchanges[$modelName]->setName($name);
            //设置类型
            $this->_exchanges[$modelName]->setType($type);
            //是否持久化
            if ($declare) {
                $this->_exchanges[$modelName]->setFlags(\AMQP_DURABLE);
            }
            $this->_exchanges[$modelName]->declareExchange();
        }
        return $this->_exchanges[$modelName];
    }

    /**
     * 创建|获取队列
     * @param string $modelName
     * @param string $name
     * @param bool $declare
     * @param string $exchange_name
     * @param string $route_key
     * @return \AMQPQueue
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     * @throws \AMQPChannelException
     */
    public function getQueue(string $modelName, string $name, bool $declare, string $exchange_name, string $route_key): \AMQPQueue
    {
        if (empty($this->_queue[$modelName]) || !$this->_queue[$modelName] instanceof \AMQPQueue) {
            $this->_queue[$modelName] = new \AMQPQueue($this->_channel);
            //设置队列名称
            $this->_queue[$modelName]->setName($name);
            //是否持久化
            if ($declare) {
                $this->_queue[$modelName]->setFlags(AMQP_DURABLE);
            }
            $this->_queue[$modelName]->declareQueue();
            //绑定
            $this->_queue[$modelName]->bind($exchange_name, $route_key);
        }
        return $this->_queue[$modelName];
    }

    /**
     * 创建
     * @param string $modelName
     * @param BaseQueueRoute $queueRoute
     * @return \AMQPQueue|false
     */
    public function createQueue(string $modelName, BaseQueueRoute $queueRoute)
    {
        try {
            $this->getConnection();
            $this->getChannel($queueRoute->getQos());
            $this->getExchange($modelName, $queueRoute->getExchangeName(), $queueRoute->getExchangeType(), $queueRoute->getDurable());
            $this->getQueue($modelName, $queueRoute->getQueneName(), $queueRoute->getDurable(), $queueRoute->getExchangeName(), $queueRoute->getRouteKey());
        } catch (\Exception $exception) {
            $this->_exception = $exception;
            return false;
        }
        return $this->_queue[$modelName];
    }


    /**
     * 发布消息
     * @param BaseQueueRoute $queueRoute
     * @param string|bool $message
     * @param bool $closeConnect 是否关闭连接
     * @return array  array[0]:发布是否成功  array[1]:异常信息
     */
    public function publish(BaseQueueRoute $queueRoute, $message, bool $closeConnect = false): array
    {
        try {
            $modelName = get_class($queueRoute);
            $this->createQueue($modelName, $queueRoute);
            $this->_exchanges[$modelName]->publish(
                $this->formatData($message),
                $queueRoute->getRouteKey(),
                AMQP_NOPARAM,
                [
                    'content_type'  => 'text/plain',    //协议
                    'delivery_mode' => $queueRoute->getDurable() ? 2 : 1, //是否持久化
                ]
            );
            if ($closeConnect) {
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
    public function closeConnection(): bool
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
            return \json_encode($data, \JSON_UNESCAPED_UNICODE);
        }
        return (string)$data;
    }
}