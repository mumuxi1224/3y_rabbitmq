<?php

namespace Mmx\Quene;

abstract class BaseQueneRoute
{
    /**
     * 交换机名称
     * @var string
     */
    protected $exchange_name;

    final public function getExchangeName(){
        return (string)$this->exchange_name;
    }
    /**
     * 交换机类型
     * @var string
     */
    protected $exchange_type;

    final public function getExchangeType(){
        return (string)$this->exchange_type;
    }

    /**
     * 队列名称
     * @var string
     */
    protected $quene_name;

    final public function getQueneName(){
        return (string)$this->quene_name;
    }
    /**
     * 路由键
     * @var string
     */
    protected $route_key = '';

    final public function getRouteKey(){
        return (string)$this->route_key;
    }

    /**
     * 每次执行消费的间隔时间
     * @var float
     */
    protected $time_interval = 0.1;
    /**
     * 单例存储对象的容器
     * @var array
     */
    static $_instance = [];

    /**
     * 单例模式存储
     * @return mixed
     */
    public static function instance(){
        //调用的类名
        $calledClass = get_called_class();
        //如果不存在容器中
        if ( !isset(self::$_instance[ $calledClass ]) || !self::$_instance[ $calledClass ] instanceof self){
            self::$_instance[ $calledClass ] = new $calledClass;
        }
        return self::$_instance[ $calledClass ];
    }

    /**
     * @return mixed
     */
    abstract function entrance();

    abstract function consume();
}
