<?php
require './vendor/autoload.php';
$rabbitMq = [
    'host'     => '127.0.0.1',
    'port'     => '5672',
    'username' => 'admin',
    'password' => 'admin',
    'vhost'    => '/'
];
$conusme  = new \Mmx\Quene\BaseQueneConsumer($rabbitMq);
// 发布消息 数组格式会默认json_encode()
for ($i=1;$i<=10;$i++){
//    \Test\Test::instance()->publish($i);
}
\Test\Test::instance()->publish(1);
\Test\Test2::instance()->publish([1]);
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