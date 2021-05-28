<?php
namespace Test;

class Test4 extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test4_exchange_name';
    protected $quene_name = 'test4_quene';

    public function consume(string $message): bool
    {
        return true;
    }

}