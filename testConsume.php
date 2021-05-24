<?php
require './vendor/autoload.php';
$rabbitMq = [
    'host'     => '127.0.0.1',
    'port'     => '5672',
    'username' => 'admin',
    'password' => 'admin',
    'vhost'    => '/',
    'stomp'     => '127.0.0.1:61613',
    'debug'     =>true,
];
$conusme  = new \Mmx\Quene\BaseQueneConsumer($rabbitMq);
// 将业务端队列注册到服务中
$conusme->register(\Test\Test::class);
$conusme->register(\Test\Test2::class);
// 批量注册
//$conusme->registerMult([\Test\Test::class,\Test\Test2::class]);
// 进程数
$conusme->count = 4;
//日志
$conusme->_log_path = 'log';
// 端口复用
$conusme->reusePort = true;
// 启动服务
\Mmx\Quene\BaseQueneConsumer::runAll();