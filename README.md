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
use Mmx\Quene\BaseRabbitmq;

class Test extends \Mmx\Quene\BaseQueneRoute {
    protected $exchange_name = 'test_exchange_name';
    protected $quene_name = 'durable_quene';

    public function consume(string $message): bool
    {
        var_dump('test1');
        return true;
    }


    public function onSuccess(string $message)
    {
    }

}
```

publish示例：

```
<?php
require './vendor/autoload.php';
$rabbitMq = [
    'host'     => '127.0.0.1',
    'port'     => '5672',
    'username' => 'admin',
    'password' => 'admin',
    'vhost'    => '/',
];
$conusme  = \Mmx\Quene\BaseRabbitmq::instance();
$conusme->getConnection($rabbitMq);
\Test\Test::instance()->publish(1);
\Test\Test2::instance()->publish([1]);

```

consume实例

```
<?php
require './vendor/autoload.php';
$rabbitMq = [
    'host'     => '127.0.0.1',
    'port'     => '5672',
    'username' => 'admin',
    'password' => 'admin',
    'vhost'    => '/',
    'stomp'    => '127.0.0.1:61613',
    'debug'    => true,
];

$conusme = new \Mmx\Quene\BaseQueneConsumer($rabbitMq);
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
```

