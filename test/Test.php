<?php
namespace Test;
use Mmx\Quene\BaseRabbitmq;

class Test extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test_exchange_name';
    protected $quene_name = 'durable_quene';

    public function consume(string $message): bool
    {
        var_dump('test1');
        return true;
    }


    public function onSuccess(string $message)
    {
    }

}