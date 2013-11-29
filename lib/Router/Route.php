<?php

class Router_Route
{
    /**
     * URL of this Route
     * @var string
     */
    private $url;

    /**
     * Accepted HTTP methods for this route
     * @var array
     */
    private $methods = array('GET', 'POST', 'PUT', 'DELETE');

    /**
     * Target controller for this route
     * @var mixed
     */
    private $controller;

    /**
     * Target for this route, can be anything.
     * @var mixed
     */
    private $action;

    /**
     * The name of this route, used for reversed routing
     * @var string
     */
    private $name;

    /**
     * Custom parameter filters for this route
     * @var array
     */
    private $filters = array();

    /**
     * Array containing parameters passed through request URL
     * @var array
     */
    private $parameters = array();

    public function __get($var)
    {
        $method = 'get_' . strtolower($var);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return null;
    }

    public function __set($var, $val)
    {
        $method = 'set_' . strtolower($var);
        if (method_exists($this, $method)) {
            $this->$method($val);
            return true;
        }
        return null;
    }

    public function get_url()
    {
        return $this->url;
    }

    public function get_nobase()
    {
        return substr($this->url, strlen(Router::instance()->get_base()));
    }

    public function set_url($url)
    {
        $this->url = '/' . ltrim((string)$url, '/');
        return $this;
    }

    public function get_controller()
    {
        return $this->controller;
    }

    public function set_controller($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    public function get_action()
    {
        return $this->action;
    }

    public function set_action($action)
    {
        $this->action = $action;
        return $this;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function set_name($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    public function get_parameters()
    {
        return $this->parameters;
    }

    public function set_parameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function get_params()
    {
        return $this->get_parameters();
    } // alias for getParameters();
    public function set_params(array $parameters)
    {
        $this->set_parameters($parameters);
        return $this;
    } // alias for setParameters();

    public function get_target()
    {
        return $this->get_controller() . '/' . $this->get_action();
    }

    public function set_target($target)
    {
        $target = explode('/', $target);
        $this->get_controller($target[0]);
        $this->get_action($target[1]);
        return $this;
    }

    public function get_methods()
    {
        return $this->methods;
    }

    public function set_methods($methods)
    {
        if (is_string($methods)) {
            $methods = explode(',', $methods);
        }
        if (!is_array($methods)) {
            throw new RouteException('Method set_methods() must be either comma separated string, or array of methods');
        }

        $this->methods = [];
        foreach ($methods as $method) {
            $this->add_method($method);
        }
        return $this;
    }

    public function add_method($method)
    {
        $method = strtoupper((string)$method);
        if (in_array($method, $this->methods)) {
            return $this;
        }
        if (!in_array($method, array('GET', 'POST', 'PUT', 'DELETE'))) {
            throw new RouteException('Methods argument may only be one or more GET, POST, PUT or DELETE');
        }
        $this->methods[] = $method;
        return $this;
    }

    public function remove_method($method)
    {
        $method = strtoupper((string)$method);
        if (!in_array($method, $this->methods)) {
            return $this;
        }
        $this->methods = array_diff($this->methods, array($method));
        return $this;
    }

    public function get_filters()
    {
        return $this->filters;
    }

    public function set_filters($filters)
    {
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }
        return $this;
    }

    public function add_filter($filter, $regex)
    {
        if (stristr($this->url, ':' . $filter)) {
            switch ($regex) {
                case 'int':
                case 'integer':
                    $regex = '([0-9]+)';
                    break;
                case 'str':
                case 'string':
                    $regex = '([a-z0-9_\-]+)';
                    break;
                case 'bool':
                case 'boolean':
                    $regex = '([true|false|1|0]+)';
                    break;
                default:
                    if (substr($regex, 0, 1) != '(') {
                        $regex = '(' . $regex;
                    }
                    if (substr($regex, -1) != ')') {
                        $regex = $regex . ')';
                    }
            }
            $this->filters[$filter] = $regex;
        }
        return $this;
    }

    public function remove_filter($filter)
    {
        if (isset($this->filters[$filter])) {
            unset($this->filters[$filter]);
        }
        return $this;
    }

    public function get_regex()
    {
        $filters = & $this->filters;
        return preg_replace_callback(
            '/:(\w+)/',
            function ($matches) use (&$filters) {
                if (isset($matches[1]) && isset($filters[$matches[1]])) {
                    return $filters[$matches[1]];
                }

                return '(\w+)';
            },
            $this->url
        );
    }

    public function __toString()
    {
        //return Router::instance()->get_base() . $this->url;
    }

}