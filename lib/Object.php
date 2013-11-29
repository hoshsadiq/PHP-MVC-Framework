<?php

class Object implements /*ArrayAccess, */
    Iterator, Countable
{
    private $_data = array();
    protected $_session = array();

    private static $_instance = null;

    private function __clone()
    {
    }

    public function __construct()
    {
        self::$_instance = & $this;
        $this->_session = & Session::instance();
    }

    public static function &instance()
    {
        if (self::$_instance == null) {
            new self();
        }
        return self::$_instance;
    }

    public static function __callStatic($name, $args)
    {
        call_user_method_array($name, self::instance(), $args);
    }

    public function __call($name, $args)
    {
        if (preg_match('/^([s|g]et)_([a-zA-Z_][a-zA-Z0-9_]*)$/', $name, $matches)) {
            if ($matches[1] == 'set') {
                return $this->{Inflector::underscore($name)} = $args[0];
            }
            return $this->{Inflector::underscore($name)};
        }
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function __get($name)
    {
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        }
        return null;
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
    }

    public function rewind()
    {
        reset($this->_data);
    }

    public function current()
    {
        return current($this->_data);
    }

    public function key()
    {
        return key($this->_data);
    }

    public function next()
    {
        return next($this->_data);
    }

    public function valid()
    {
        return $this->_data() !== false;
    }

    public function count()
    {
        return count($this->_data);
    }
}