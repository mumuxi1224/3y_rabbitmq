<?php

namespace Test;

class Test5 extends \Mmx\Queue\BaseQueueRoute
{
    protected $exchange_name = 'test5_exchange_name';
    protected $queue_name = 'test5_queue';

    public function consume(string $message): bool
    {
        return true;
    }

}