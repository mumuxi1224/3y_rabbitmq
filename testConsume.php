<?php
require './vendor/autoload.php';
$rabbitMq = [
    'host'              => '192.168.4.92',
    'port'              => '5673',
    'username'          => 'admin',
    'password'          => 'admin',
    'vhost'             => '/',
    'stomp'             => '192.168.4.92:61615',
    'debug'             => false,
    'maxSendBufferSize' => 5,
];
$conusme  = new \Mmx\Quene\BaseQueneConsumer($rabbitMq);
// 将业务端队列注册到服务中
$conusme->register(\Test\Test::class);
$conusme->register(\Test\Test2::class);
$conusme->register(\Test\Test3::class);
$conusme->register(\Test\Test4::class);
$conusme->register(\Test\Test5::class);
// 批量注册
//$conusme->registerMult([\Test\Test3::class,\Test\Test4::class,\Test\Test5::class]);
// 进程数
$conusme->count = 4;
//日志
$conusme->_log_path = 'log';
// 端口复用
$conusme->reusePort = true;
maxSendBufferSize
// 启动服务
\Mmx\Quene\BaseQueneConsumer::runAll();