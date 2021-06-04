# 3y_rabbitmq

一个子类需要继承\Mmx\Quene\BaseQueneRoute，再注册到服务中，消息是持久化保存

发送消息使用的php-rabbitmq扩展，接受消息使用的是stomp协议，workman/stomp异步组件支持，所以需要在rabbitmq端开启对stomp的支持，需要在rabbitmq服务端执行以下命令

```
rabbitmq-plugins enable rabbitmq_stomp
```

consume()方法：消费队列时的回调，传入的是字符串格式的消息，手动调用ack()或不调用，会在consume()方法执行完毕后执行ack，调用nack(),会在consume()方法执行完毕后执行nack

onSuccess()方法：消费成功后的回调，传入的是字符串格式的消息

消费时传入rabbitMq的参数实例：
```
$rabbitMq = [
    'host'              => '192.168.4.92', 
    'port'              => '5673',
    'username'          => 'admin',
    'password'          => 'admin',
    'vhost'             => '/',
    'stomp'             => '192.168.4.92:61615', //stomp监听的地址
    'debug'             => false, //是否开启debug，开启后每次stomp的行为都有相应输出
    'maxSendBufferSize' => 10, //最大缓冲区的大小，单位M
    'prefetch_count'    => 10, //stomp每次最多预读值的大小，与rabbimt中channel设置qos的行为一样
];
```

安装：

```
composer require mumuxi1224/3y_rabbitmq
```

模型示例：

```php
<?php
namespace Test;

class Test2 extends \Mmx\Queue\BaseQueueRoute {
    protected $exchange_name = 'test2_exchange_name';
    protected $queue_name = 'test2_queue';

    public function consume(string $message)
    {
        $this->ack();
//        $this->nack();
    }

    public function onRetryError(string $message)
    {
       var_dump($message);
    }
}
```

publish示例：

```
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


```

consume实例

```
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
```

