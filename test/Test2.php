<?php
namespace Test;
use Cassandra\Varint;
use Mmx\Quene\BaseRabbitmq;

class Test2 extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test2_exchange_name';
    protected $quene_name = 'non_durable_quene';
    protected $durable = true;
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

        var_dump('test2');
        return false;
    }

    public function onRetryError(string $message)
    {
       var_dump($message);
    }
}