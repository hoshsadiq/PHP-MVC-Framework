<?php

/**
 * @todo add events (after overheard)
 * @todo plugins (after overheard)
 */

final class Maker
{
    private static $_registry = array();
    public static $db = array();

    /**
     * Initiates the application.
     * Loads the autoloader and connects to MySQL
     */
    public static function init()
    {
        self::setReporting();
        require_once('Autoload.php');
        require_once('Core.php');
        require_once('Instances.php');
        require_once('Functions.php');
        new Core_Autoload();
        date_default_timezone_set('Europe/London');
        //set_error_handler('Maker::error', E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);

        Mysql::connect('root', '', 'test');
        Session::start();
        User::loggedin();

        self::def('IS_XHR', Router::instance()->is_xhr());
        self::def('IS_MOBILE', Router::instance()->is_mobile());
    }

    /**
     * Logs errors if error logging is enabled
     * @param string $log The line to log.
     * @param string $file The file to log to
     * @param boolean $stack_trace Whether or not to log the stack trace (Yet to be implemented).
     * @param string $type The log folder to put this into
     * @todo implement $stack_trace
     */
    public static function log($log, $file = 'user_error', $stack_trace = false, $type = 'system')
    {
        if (Maker::get('log_errors')) {
            $folder = ABSPATH . DS . 'logs' . DS . $type;
            if (!file_exists($folder) || !is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            $file = $folder . DS . $file . '.log';
            $f = fopen($file, 'a');
            fputs($f, $log, strlen($log));
            fclose($f);
        }
    }

    public static function salt($word, $md5 = true, $salt = CONFIG::SALT)
    {
        if ($md5 === null) {
            $md5 = true;
        }
        $ret = $salt . ':' . $word . ':' . $salt;
        return ($md5 == true) ? md5($ret) : $ret;
    }

    public static function posted($method = 'post')
    {
        return strtolower($_SERVER['REQUEST_METHOD']) == $method;
    }

    /**
     * Register a new variable
     *
     * @param string $key
     * @param mixed $value
     * @param bool $graceful
     * @throws Mage_Core_Exception
     */
    public static function set($key, $value)
    {
        self::$_registry[$key] = $value;
    }

    /**
     * Unregister a variable from register by key
     *
     * @param string $key
     */
    public static function unregister($key)
    {
        if (isset(self::$_registry[$key])) {
            if (is_object(self::$_registry[$key]) && (method_exists(self::$_registry[$key], '__destruct'))) {
                self::$_registry[$key]->__destruct();
            }
            unset(self::$_registry[$key]);
        }
    }

    /**
     * Retrieve a value from registry by a key
     *
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        if (isset(self::$_registry[$key])) {
            return self::$_registry[$key];
        }
        return null;
    }

    /**
     * Retrieve model object
     *
     * @param   string $modelClass
     * @param   array $arguments
     * @return  Model
    public static function getModel($modelClass = '')
     * {
     * return isset($this->model->$modelClass) ? $this->model->$modelClass : new Model($modelClass);
     * }
     */

    /**
     * Set the error reporting based on development environment settings
     * @param boolean $report Whether or not to report errors
     */
    public static function setReporting($report = true)
    {
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set('display_errors', 'On');
        ini_set('log_errors', 'Off');
        ini_set('error_log', ABSPATH . DS . 'logs' . DS . 'system' . DS . 'error.log');
        //Maker::set('log_errors', true);
        //Maker::set('show_errors', $report);
    }

    /**
     * Generate a random string
     *
     * @param integer $length
     * @return string
     */
    public static function rnd_str($length = 32)
    {
        $alphabet = 'abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789';
        $count = strlen($alphabet) - 1;
        $pass = '';
        for ($i = 0; $i < $length; $i++) {
            srand((double)microtime() * 1000000000000);
            $rand = rand(0, $count);
            $pass .= $alphabet[$rand];
        }
        return $pass;
    }

    // used so we can have a before define event call
    // e.g. addEvent('define-$
    public static function def($name, $value, $case_insensitive = false)
    {
        if (defined($name)) {
            return;
        }

        $name = (string)$name;
        $case_insensitive = (bool)$case_insensitive;

        return define($name, $value, $case_insensitive);
    }

    public static function redirect($location)
    {
        header('Location: ' . $location);
        exit;
    }
}