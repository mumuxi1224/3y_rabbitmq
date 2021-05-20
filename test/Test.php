<?php
namespace Test;
use Mmx\Quene\BaseRabbitmq;

class Test extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test_exchange_name';
    protected $quene_name = 'test_quene_name';
    protected $durable = true;

    public function consume(string $message): bool
    {
        var_dump(date('Y-m-d H:i:s'));
        var_dump($message);
        if ($message >3){
            return false;
        }
        return true;
    }


    public function onSuccess(string $message)
    {
        var_dump('success');
    }

}