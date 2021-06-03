<?php

namespace Test;

class Test2 extends \Mmx\Queue\BaseQueueRoute
{
    protected $exchange_name = 'test2_exchange_name';
    protected $queue_name = 'test2_queue';

    public function consume(string $message)
    {
        $this->ack();
    }

}