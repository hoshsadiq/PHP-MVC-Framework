<?php

class Event extends Object
{
    private static $events = array();

    public static function register($event, $fn)
    {
        if (!is_callable($fn)) {
            throw new EventException('$fn is not callable and not closure');
        }
        self::$events[$event][] = $fn;
    }

    public static function fire($event)
    {
        if (isset(self::$events[$event])) {
            $params = func_get_args();
            array_shift($params);

            foreach (self::$events[$event] as $fn) {
                call_user_func_array($fn, $params);
            }
        }
    }
}