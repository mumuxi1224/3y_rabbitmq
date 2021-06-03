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
    'maxSendBufferSize' => 10,
    'prefetch_count'    => 1500,
];
$conusme  = new \Mmx\Queue\BaseQueueConsumer($rabbitMq);
//$rdsConfig = [
//    'host'       => '192.168.4.228',
//    'port'       => '6379',
//    'password'   => 'Redis@fanjiao',
//    'select'     => 0,
//    'timeout'    => 2.5,   # 秒为单位
//    'expire'     => 0,
//    'persistent' => false, # 持久化
//    'prefix'     => '',
//    'index'      => 17
//];
//$redis = BaseRedis::getConnection($rdsConfig);

// 将业务端队列注册到服务中
$conusme->register(\Test\Test::class);
//$conusme->register(\Test\Test2::class);
//$conusme->register(\Test\Test3::class);
//$conusme->register(\Test\Test4::class);
//$conusme->register(\Test\Test5::class);
// 批量注册
$conusme->registerMulti([\Test\Test3::class,\Test\Test4::class,\Test\Test5::class]);
// 进程数
$conusme->count = 3;
//日志
$conusme->_log_path = 'log';
// 端口复用
$conusme->reusePort = true;
// 启动服务
\Mmx\Queue\BaseQueueConsumer::runAll();