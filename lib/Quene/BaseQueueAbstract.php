<?php

namespace Mmx\Quene;
abstract class BaseQueueAbstract
{
    const EXCHANGE_TYPE_DIRECT = 'direct';  //直连交换机
    const EXCHANGE_TYPE_FANOUT = 'fanout';  //扇形交换机
    const EXCHANGE_TYPE_TOPIC  = 'topic';   //主题交换机
    const EXCHANGE_TYPE_HEADER = 'header';  //头部交换机
}