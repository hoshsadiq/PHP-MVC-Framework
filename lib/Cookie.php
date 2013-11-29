<?php

/*
 * Warning: xdebug interferes by sending output before cookies,
 * so be cautious with debugging
 */
define('DEBUG', false);
define('SESSION', null);
define('ONEDAY', 86400);
define('SEVENDAYS', 604800);
define('THIRTYDAYS', 2592000);
define('SIXMONTHS', 15768000);
define('ONEYEAR', 31536000);
define('LIFETIME', -1); // 2030-01-01 00:00:00

class Cookie
{

    public static function set($name, $val, $expiry = ONEYEAR)
    {
        setcookie($name, $val, $expiry);
    }

    public static function delete($name)
    {
        setcookie($name, '', time() - 1);
    }
}

?>