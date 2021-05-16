<?php

namespace Mmx\Quene;

use Workerman\Timer;
use Workerman\Worker;

class BaseRabbitmq
{
    /**
     * rabbitmq链接
     * @var \AMQPConnection
     */
    protected $_connection = null;

    protected $_channel = null;

    /**
     * 创建rabbitmq链接
     * @param array $config
     * @param float|float $timeout
     * @return \AMQPConnection
     * @throws \AMQPConnectionException
     */
    public function createConnection(array $config = [],float $timeout = 3.0){
        if(!$this->_connection instanceof \AMQPConnection){
            if (!isset($config['host']))$config['host'] = '';
            if (!isset($config['vhost']))$config['vhost'] = '';
            if (!isset($config['port']))$config['port'] = '';
            if (!isset($config['username']))$config['username'] = '';
            if (!isset($config['password']))$config['password'] = '';
            $this->_connection = new \AMQPConnection([
                'host'            => $config['host'],
                'virtual'         => $config['vhost'],
                'port'            => $config['port'],
                'login'           => $config['username'],
                'password'        => $config['password'],
                'connect_timeout' => $timeout
            ]);
        }
        $this->_connection->connect();
        return $this->_connection;
    }

    //创建信道
    public function channel(int $count = 1){
        if(!$this->_channel instanceof \AMQPChannel){
            $this->_channel = new \AMQPChannel($this->_connection);
            $this->_channel->qos(null, $count);
        }
        $this->_channel_id = $this->_channel->getChannelId();
        return $this->_channel;
    }


    public function exchange(strgin $exchange_name,string $name = null, string $type = null){
        if(!$this->_exchange instanceof \AMQPExchange){
            $this->_exchange = new \AMQPExchange($this->_channel);
        }
        $this->_exchange->setName($this->_exchange_name = $name !== null ? $name : $this->_exchange_name);
        $this->_exchange->setType($this->_exchange_type = $type !== null ? $type : $this->_exchange_type);
        if($this->_declare){
            $this->_exchange->setFlags(AMQP_DURABLE);
            $this->_exchange->declareExchange();
        }
        return $this->_exchange;
    }
}