<?php
namespace Test;

class Test2 extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test2_exchange_name';
    protected $quene_name = 'test2_quene';

    public function consume(string $message)
    {
        $this->ack();
    }

    public function onRetryError(string $message)
    {
       var_dump($message);
    }
}