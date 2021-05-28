<?php
namespace Test;

class Test5 extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test5_exchange_name';
    protected $quene_name = 'test5_quene';

    public function consume(string $message): bool
    {

        return false;
    }

}