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
