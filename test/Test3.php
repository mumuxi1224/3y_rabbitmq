<?php

namespace Test;

class Test3 extends \Mmx\Queue\BaseQueueRoute
{
    protected $exchange_name = 'test3_exchange_name';
    protected $queue_name = 'test3_queue';

    public function consume(string $message): bool
    {
        return true;
    }

}