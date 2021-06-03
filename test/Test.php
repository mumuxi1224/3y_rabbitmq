<?php

namespace Test;

class Test extends \Mmx\Queue\BaseQueueRoute
{
    protected $exchange_name = 'test_exchange_name';
    protected $queue_name = 'test_queue';

    public function consume(string $message): bool
    {
        return true;
    }


    public function onSuccess(string $message)
    {
    }

}