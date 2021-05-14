<?php
namespace Test;
class Test extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'exchange_name';
    protected $exchange_type = 'topic';
    protected $quene_name = 'quene_name';
    protected $route_key = '';
    public function entrance()
    {
        // TODO: Implement entrance() method.
    }
    public function consume()
    {
        echo '123';
        // TODO: Implement consume() method.
    }
}