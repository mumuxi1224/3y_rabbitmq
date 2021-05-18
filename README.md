# 3y_rabbitmq

一个子类需要继承\Mmx\Quene\BaseQueneRoute，再注册到服务中，服务启动后，会以非阻塞式定时的（由模型中的$time_interval决定）

consume()方法：消费队列时的回调，传入的是字符串格式的消息，返回true时会自动确认（ack）,返回false会丢弃消息，捕获到异常时消息会重回队列

onSuccess()方法：消费成功后的回调，传入的是字符串格式的消息

onError()方法：消费队列捕获异常后的回调，传入的是异常和字符串格式的消息

安装：

```
composer require mumuxi1224/3y_rabbitmq
```

模型示例：

```php
<?php
namespace Test;
use Mmx\Quene\BaseRabbitmq;

class Test extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test_exchange_name';
    protected $exchange_type = AMQP_EX_TYPE_DIRECT;
    protected $quene_name = 'test_quene_name';
    protected $route_key = '';

    public function consume(string $message): bool
    {
        var_dump(date('Y-m-d H:i:s'));
        var_dump($message);
        return true;
    }


    public function onSuccess(string $message)
    {
        var_dump('success');
    }

    public function onError(\Exception $exception, string $message)
    {
        var_dump($exception->getMessage());
        var_dump($message);
    }

}
```

启动示例：

```
<?php
require './vendor/autoload.php';
$rabbitMq = [
    'host'     => '192.168.4.92',
    'port'     => '5672',
    'username' => 'admin',
    'password' => 'admin',
    'vhost'    => '/'
];
$conusme  = new \Mmx\Quene\BaseQueneConsumer($rabbitMq);
// 发布消息 数组格式会默认json_encode()
\Test\Test::instance()->publish(1);
\Test\Test2::instance()->publish([1]);
// 将业务端队列注册到服务中
$conusme->register(\Test\Test::class);
$conusme->register(\Test\Test2::class);
// 批量注册
//$conusme->registerMult([\Test\Test::class,\Test\Test2::class]);
// 进程数
$conusme->count = 4;
// 端口复用
$conusme->reusePort = true;
// 启动服务
\Mmx\Quene\BaseQueneConsumer::runAll();
```