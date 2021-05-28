# 3y_rabbitmq

一个子类需要继承\Mmx\Quene\BaseQueneRoute，再注册到服务中，消息是持久化保存

发送消息使用的php-rabbitmq扩展，接受消息使用的是stomp协议，workman/stomp异步组件支持，所以需要在rabbitmq端开启对stomp的支持，需要在rabbitmq服务端执行以下命令

```
rabbitmq-plugins enable rabbitmq_stomp
```

consume()方法：消费队列时的回调，传入的是字符串格式的消息，返回true时会自动确认（ack）,返回false会重试一定次数（nack）后如果还是false会将消息重新投递到队列底部

onSuccess()方法：消费成功后的回调，传入的是字符串格式的消息



安装：

```
composer require mumuxi1224/3y_rabbitmq
```

模型示例：

```php
<?php
namespace Test;

class Test2 extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test2_exchange_name';
    protected $quene_name = 'test2_quene';

    public function consume(string $message)
    {
        $this->ack();
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
$conusme  = \Mmx\Quene\BaseRabbitmq::Connection($rabbitMq);
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
$limit  = 50000;
for ($i = 1; $i <= $limit; $i++) {
    list($res1,) = \Test\Test::instance()->publish(uniqid());
    if (!$res1) $result['test1']++;
    list($res2,) = \Test\Test2::instance()->publish(uniqid());
    if (!$res2) $result['test2']++;
    list($res3,) = \Test\Test3::instance()->publish(uniqid());
    if (!$res3) $result['test3']++;
    list($res4,) = \Test\Test4::instance()->publish(uniqid());
    if (!$res4) $result['test4']++;
    list($res5,) = \Test\Test5::instance()->publish(uniqid());
    if (!$res5) $result['test5']++;
}
$etime = microtime(true);
$end   = memory_get_usage(true);
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
```

