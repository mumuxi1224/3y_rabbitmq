<?php

namespace Test;

class Test3 extends \Mmx\Quene\BaseQueneRoute
{
    protected $exchange_name = 'test3_exchange_name';
    protected $quene_name    = 'test3_quene';

    public function consume(string $message): bool
    {
        return true;
    }

}