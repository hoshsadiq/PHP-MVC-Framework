<?php

/**
 * @package Overheard
 * @version $Id: ip.php 70 03-10-2010 13:45:27Z Hoshang Sadiq $
 * @copyright (c) 2010 Hoshang Sadiq
 * @author Hoshang Sadiq
 */

/**
 * Simple IP class to get the IP info
 */
class Ip
{
    public static $_instance = null;

    private function __clone()
    {
    }

    public function __construct()
    {
        self::$_instance = & $this;
    }

    public static function &instance()
    {
        if (self::$_instance == null) {
            new self();
        }
        return self::$_instance;
    }

    /**
     * __get()
     * Get the user's IP addresses
     *
     * @param bool $inet
     * @return string
     */
    public function __get($var)
    {
        $items = array('ip' => 'client_ip', 'x' => 'http_x', 'addr' => 'remote_addr');
        if (isset($items[$var])) {
            return $this->{$items[$var]}();
        }
        if (method_exists($this, $var)) {
            return $this->$var();
        }
        return null;
    }

    /**
     * getip()
     * Get the user's IP addresses
     *
     * @param bool $inet
     * @return string
     */
    public function getip($inet = false)
    {
        $ip = new stdClass;
        $items = array('ip' => 'client_ip', 'x' => 'http_x', 'addr' => 'remote_addr');
        foreach ($items as $key => $method) {
            $ip->$key = $this->$method($inet);
        }
        return $ip;
    }

    /**
     * realip()
     * Get the user's real IP address
     *
     * @param bool $inet
     * @return string
     */
    public function realip($inet = false)
    {
        if ($this->client_ip() != '') {
            $ip = $this->client_ip($inet);
        } elseif ($this->http_x() != '') {
            $ip = $this->http_x($inet);
        } else {
            $ip = $this->remote_addr($inet);
        }
        return $ip;
    }

    /**
     * client_ip()
     * returns the user's HTTP_CLIENT_IP
     *
     * @param bool $inet
     * @return string
     */
    public function client_ip($inet = false)
    {
        if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != '') {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = getenv('HTTP_CLIENT_IP') ? getenv('HTTP_CLIENT_IP') : '';
        }
        return ($inet) ? inet_pton($ip) : $ip;
    }

    /**
     * http_x()
     * Returns the HTTP_X_FORWARDED_FOR of a user
     *
     * @param bool $inet
     * @return string
     */
    public function http_x($inet = false)
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = getenv('HTTP_X_FORWARDED_FOR') ? getenv('HTTP_X_FORWARDED_FOR') : '';
        }
        return ($inet) ? inet_pton($ip) : $ip;
    }

    /**
     * remote_addr()
     * Returns a user's remote address (IP Address)
     *
     * @param bool $inet
     * @return
     */
    public function remote_addr($inet = false)
    {
        $ip = (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '') ? $_SERVER['REMOTE_ADDR'] : getenv(
            'REMOTE_ADDR'
        );
        return ($inet) ? inet_pton($ip) : $ip;
    }

    /**
     * hostbyip()
     * Returns the host of the user
     *
     * @return string
     */
    public function hostbyip()
    {
        return (gethostbyaddr($this->realip()) != '') ? gethostbyaddr($this->realip()) : '';
    }
}

?>