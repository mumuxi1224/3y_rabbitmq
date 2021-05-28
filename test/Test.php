<?php
namespace Test;

class Test extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test_exchange_name';
    protected $quene_name = 'test_quene';

    public function consume(string $message): bool
    {
        return true;
    }


    public function onSuccess(string $message)
    {
    }

}