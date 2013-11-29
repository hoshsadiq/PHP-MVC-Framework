<?php

/**
 * This handles sessions in a more easy manner.
 * @package Lecture Notes
 * @version $Id: cookie.php 70 04-10-2010 00:54:33Z Hoshang Sadiq $
 * @copyright (c) 2010 Hoshang Sadiq
 * @author Hoshang Sadiq, Simon
 *
 * @todo split up error() into a) error assigning method and b) error retrieving method
 */


/**
 * This class handles cookies
 * Please note, there is security measures to make sure that cookies are not edited by third parties.
 */
class Session implements Countable, Iterator
{
    public static $instance = null;

    /**
     * The constructor.
     */
    public function __construct()
    {
        /* Nothing to do */
    }

    public static function start()
    {
        if (!session_active()) {
            ini_set('session.cookie_path', COOKIE::PATH);
            ini_set('session.cookie_domain', COOKIE::DOMAIN);
            ini_set('session.name', 'session');
            session_start();
        }
    }

    public static function message($message = null)
    {
        $flash = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : array();
        if (is_string($message)) {
            $flash[] = $message;
            $_SESSION['flash_message'] = $flash;
        } else {
            if (Maker::get('empty_session') !== false) {
                unset($_SESSION['flash_message']);
            }
            return $flash;
        }
    }

    public static function error($error = null)
    {
        $flash = isset($_SESSION['flash_error']) ? $_SESSION['flash_error'] : array();
        if (is_string($error)) {
            $flash[] = $error;
            $_SESSION['flash_error'] = $flash;
        } else {
            if (Maker::get('empty_session') != false) {
                unset($_SESSION['flash_error']);
            }
            return $flash;
        }
    }

    public static function error_count()
    {
        return isset($_SESSION['flash_error']) ? count($_SESSION['flash_error']) : 0;
    }

    public static function message_count()
    {
        return isset($_SESSION['flash_message']) ? count($_SESSION['flash_message']) : 0;
    }

    public static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if an cookie exists, returns true if it exists, false if it doesn't
     *
     * @param string $name Cookie name
     * @return bool True if cookie exists, otherwise false
     */
    public static function exists($name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Check where an cookie is empty (including null, 0 etc) or not.
     * Check http://php.net/empty for a full list.
     *
     * @param string $name Name of the cookie
     * @return bool True if cookie name is empty, false otherwise
     */
    public static function _empty($name)
    {
        return self::get($name) != '';
    }

    /**
     * Get the value of the given cookie.
     *
     * @param string $name The name of the cookie
     * @return string Value of the cookie if unedited.
     */
    public static function get($name)
    {
        if (isset($_COOKIE[$name])) {
            if ($name == session_name()) {
                return $_COOKIE[$name];
            }
            $cookie = $_COOKIE[$name];
            $md5 = substr($cookie, -32);
            $value = substr($cookie, 0, -32);
            if (md5($value . CONFIG::SALT) == $md5) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Set a cookie. If the headers haven't already been sent.
     * It returns the number of errors that have occured
     *
     * @param mixed $name Name of the cookie. This can be an associative array,
     * in which case $value is regarded as the $expiry
     * @param mixed $value Value of the cookie. This is regarded as the $expiry
     * if $name is an associative array.
     * @return int|bool True on success, error number otherwise.
     */
    public static function set($name, $value, $expiry = 0)
    {
        //global $_COOKIE;
        if (!headers_sent()) {
            $expiry = $expiry > 0 ? time() + $expiry : 0;
            if (is_array($name) && is_array($value) && count($name) == count($value)) {
                for ($i = 0; $i < count($name); $i++) {
                    if (self::_set($name[$i], $value[$i], $expiry)) {
                        $_COOKIE[$name[$i]] = self::cookie_value($value[$i]);
                    } else {
                        return false;
                    }
                }
            } // associative array means $value is expiry
            elseif (is_array($name) && is_int($value)) {
                $expiry = $value > 0 ? time() + $value : 0;
                foreach ($name as $cookie => $value) {
                    if (self::_set($cookie, $value, $expiry)) {
                        $_COOKIE[$cookie] = self::cookie_value($value);
                    } else {
                        return false;
                    }
                }
            } elseif (is_string($name) && is_string($value)) {
                if (self::_set($name, $value, $expiry)) {
                    $_COOKIE[$name] = self::cookie_value($value);
                } else {
                    return false;
                }
            } else {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Send a header to set the cookie, there is no validation here
     *
     * @param string $name Name of the cookie
     * @param string $value Value of the cookie
     * @param integer $expiry Expiry for the cookie
     */
    private static function cookie_value($value)
    {
        return strlen($value) > 0 ? $value . md5($value . CONFIG::SALT) : '';
    }

    /**
     * Send a header to set the cookie, there is no validation here
     *
     * @param string $name Name of the cookie
     * @param string $value Value of the cookie
     * @param integer $expiry Expiry for the cookie
     */
    private static function _set($name, $value, $expiry)
    {
        // make it work with subdomains
        $domain = COOKIE::DOMAIN;
        $domain = (strtolower(substr($domain, 0, 4)) == 'www.') ? substr($domain, 4) : $domain;
        $domain = (substr($domain, 0, 1) != '.') ? '.' . $domain : $domain;

        // remove port
        $port = strpos($domain, ':');
        $domain = ($port !== false) ? substr($domain, 0, $port) : $domain;

        if ($domain == '.localhost') {
            $domain = false;
        }
        return setcookie(
            $name,
            self::cookie_value($value),
            $expiry,
            COOKIE::PATH,
            $domain,
            COOKIE::SECURE,
            COOKIE::HTTPONLY
        );
    }

    /**
     * Deletes one or more cookies.
     * Returns the number of errors that have occured.
     *
     * @param mixed $name Name of the cookie, multiple names can be given for deletion through an array
     * @return int|bool True on success, error number otherwise
     */
    public static function delete($name)
    {
        $ret = array();
        if (!headers_sent()) {
            if (is_array($name)) {
                for ($i = 0; $i < count($name); $i++) {
                    $retval = self::_set($name[$i], false, 0, '', false);
                    if ($retval) {
                        unset($_COOKIE[$name[$i]]);
                    } else {
                        $ret[] = $name[$i];
                    }
                }
            } else {
                $retval = self::_set($name, false, -60, '', false);
                if ($retval) {
                    unset($_COOKIE[$name]);
                } else {
                    $ret[] = $name;
                }
            }
        } else {
            return false;
        }
        if (count($ret) > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Deletes all the cookies.
     *
     * @return int|bool True on success, error number otherwise
     */
    public static function delcookies()
    {
        $ret = array();
        foreach ($_COOKIE as $name => $val) {
            $del = self::delete($name);
            if (!$del) {
                return false;
            }
        }
        return true;
    }

    public function __get($name)
    {
        return self::get($name);
    }

    public function __set($name, $value)
    {
        return self::set($name, $value);
    }

    public function __isset($name)
    {
        return self::exists($name);
    }

    public function __unset($name)
    {
        return self::delete($name);
    }

    public function rewind()
    {
        reset($_COOKIE);
    }

    public function current()
    {
        current($_COOKIE);
        return $this->{key($_COOKIE)};
    }

    public function key()
    {
        return key($_COOKIE);
    }

    public function next()
    {
        return next($_COOKIE);
    }

    public function valid()
    {
        return key($_COOKIE) !== null;
    }

    public function count()
    {
        return count($_COOKIE);
    }
}

