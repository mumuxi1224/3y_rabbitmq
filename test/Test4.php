<?php

namespace Test;

class Test4 extends \Mmx\Queue\BaseQueueRoute
{
    protected $exchange_name = 'test4_exchange_name';
    protected $queue_name = 'test4_queue';

    public function consume(string $message): bool
    {
        return true;
    }

}