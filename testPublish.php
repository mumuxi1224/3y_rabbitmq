<?php
require './vendor/autoload.php';
$rabbitMq = [
    'host'     => '192.168.4.92',
    'port'     => '5673',
    'username' => 'admin',
    'password' => 'admin',
    'vhost'    => '/',
];
$stime    = microtime(true);
\Mmx\Queue\BaseRabbitmq::Connection($rabbitMq);
$etime    = microtime(true);
var_dump('连接耗时时间' . ($etime - $stime));
$result = [
    'test1' => 0,
    'test2' => 0,
    'test3' => 0,
    'test4' => 0,
    'test5' => 0,
];
$stime  = microtime(true);
$start  = memory_get_usage(true);
$limit  = 10000;
for ($i = 1; $i <= $limit; $i++) {
//    $bastRabbitMq = new \Mmx\Quene\BaseRabbitmq();
//    $bastRabbitMq->testGetConnection($rabbitMq);
//    $bastRabbitMq->publish(new \Test\Test3,1);
    list($res1,) = \Test\Test::instance()->publish(uniqid());
    if (!$res1) $result['test1']++;
    list($res2,) = \Test\Test2::instance()->publish(uniqid());
    if (!$res2) $result['test2']++;
    list($res3,) = \Test\Test3::instance()->publish(uniqid());
    if (!$res3) $result['test3']++;
//    list($res4,) = \Test\Test4::instance()->publish(uniqid());
//    if (!$res4) $result['test4']++;
//    list($res5,) = \Test\Test5::instance()->publish(uniqid());
//    if (!$res5) $result['test5']++;
}
$etime = microtime(true);
$end   = memory_get_usage(true);
var_dump('投递数量' . ($limit *1));
var_dump('耗时时间' . ($etime - $stime));
var_dump('内存占用：' . round(($end - $start) / 1024 / 1024, 2) . 'MB');
foreach ($result as $k => $v) {
    $result[$k . '_rate'] = bcdiv($v, $limit, 3);
}
var_dump($result);
