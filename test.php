<?php
require './vendor/autoload.php';
use Test\Test;
//require './lib/Quene/BaseQueneConsumer.php';
$conusme = new \Mmx\Quene\BaseQueneConsumer();
$server = \Test\Test::instance();
$conusme->register(Test::class);
# 进程数
$conusme->count  = 4;

# 端口复用
$conusme->reusePort = true;
\Mmx\Quene\BaseQueneConsumer::runAll();
//var_dump($server);
