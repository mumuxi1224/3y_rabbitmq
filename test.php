<?php
require './vendor/autoload.php';
use Test\Test;
//require './lib/Quene/BaseQueneConsumer.php';
$rabbitMq = [
    'host'      =>'192.168.4.92',
    'port'      =>'5672',
    'username'      =>'admin',
    'password'  =>'admin',
    'vhost'     =>'/'
];
$conusme = new \Mmx\Quene\BaseQueneConsumer($rabbitMq);
$server = \Test\Test::instance();
$server->publish(1);
\Test\Test2::instance()->publish([1]);
$conusme->register(Test::class);
$conusme->register(\Test\Test2::class);
# 进程数
$conusme->count  = 4;
# 端口复用
$conusme->reusePort = true;
\Mmx\Quene\BaseQueneConsumer::runAll();
//var_dump($server);
