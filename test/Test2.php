<?php
namespace Test;
use Cassandra\Varint;
use Mmx\Quene\BaseRabbitmq;

class Test2 extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test2_exchange_name';
    protected $exchange_type = AMQP_EX_TYPE_FANOUT;
    protected $quene_name = 'test2_quene_name';
    protected $route_key = '';

//    public function consume(\AMQPEnvelope $envelope, \AMQPQueue $quene)
//    {
//        $msg = $envelope->getBody();
//        var_dump(date('Y-m-d H:i:s'));
//        var_dump($msg);
//        $quene->ack($envelope->getDeliveryTag());
//        return true;
//    }

    public function consume(string $message): bool
    {
        var_dump(date('Y-m-d H:i:s'));
        var_dump($message);
        return false;
    }
}