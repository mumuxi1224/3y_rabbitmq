<?php

namespace Mmx\Core;
abstract class Instance
{
    /**
     * 单例容器
     * @var array
     */
    private static $_instances = [];

    /**
     * 对象的单例模式
     * @return static
     */
    final public static function instance()
    {
        $calledClass = get_called_class();
        # 容器中不存在
        if (!isset(self::$_instances[$calledClass]) or !self::$_instances[$calledClass] instanceof Instance) {
            return self::$_instances[$calledClass] = new $calledClass();
        }
        return self::$_instances[$calledClass];
    }
}
