<?php
namespace Test;
use Mmx\Quene\BaseRabbitmq;

class Test extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test_exchange_name';
    protected $exchange_type = AMQP_EX_TYPE_DIRECT;
    protected $quene_name = 'test_quene_name';
    protected $route_key = '';
    protected $logPath = 'test/';
//    public function entrance()
//    {
//        $data = [
//            'test1'=>1,
//            'test2'=>2
//        ];
//        return BaseRabbitmq::instance()->publish($this,$data);
//    }
//    public function consume(\AMQPEnvelope $envelope, \AMQPQueue $quene)
//    {
//        $msg = $envelope->getBody();
//        var_dump(date('Y-m-d H:i:s'));
//        var_dump($msg);
//        $quene->ack($envelope->getDeliveryTag());
//        return false;
//    }
    public function consume(string $message): bool
    {
        var_dump(date('Y-m-d H:i:s'));
        var_dump($message);
        return true;
    }


    public function onSuccess(string $message)
    {
        var_dump('success');
    }

    public function onError(\Exception $exception, string $message)
    {
        var_dump($exception->getMessage());
        var_dump($message);
    }

}