<?php

namespace yzh52521\GridCaptcha\facade;


use support\Request;

/**
 * Class GridCaptcha
 * @see \yzh52521\GridCaptcha\GridCaptcha
 * @mixin \yzh52521\GridCaptcha\GridCaptcha
 * @method array get(array $arr) static
 * @method bool check(string $key, string $code, bool $delete = true) static
 * @method bool checkRequest(Request $request, bool $delete = true) static
 *
 */
class GridCaptcha
{
    protected static $_instance = null;


    public static function instance()
    {
        if (!static::$_instance) {
            static::$_instance = new \yzh52521\GridCaptcha\GridCaptcha();
        }
        return static::$_instance;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(... $arguments);
    }
}
