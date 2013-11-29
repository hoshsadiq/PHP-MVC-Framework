<?php

/**
 * Controller class
 *
 * @package overheard
 * @author Hosh Sadiq
 * @copyright 2012
 */
abstract class Controller
{
    private $vars = array();
    protected $title = 'Default';
    protected $get = array();
    protected $post = array();
    protected $route = '';
    protected $json = true;
    protected $xhr = false;
    protected $view = null;

    final public function __construct()
    {
        $this->route = Router::instance()->get_route();
        $this->get = Mysql::escape((object)array_merge($_GET, $this->route->params), ESCAPE_AUTO, false);
        $this->post = Mysql::escape((object)$_POST, ESCAPE_AUTO, false);

        if (method_exists($this, '_construct')) {
            $this->_construct();
        }

        if (IS_XHR) {
            $this->xhr = true;
        }
    }


    final public function _pager($method)
    {
        ob_start();
        $this->$method();

        if (!$this->xhr) {
            if ($this->template != null) {
                /* this below should probably be separated into its own controller */
                $session = Session::instance();
                $header = new View('header');
                $this->view->vars(
                    array(
                        'user' => User::loggedin(),
                        'username' => $session->username,
                        'userid' => $session->userid,
                        'base' => ABSURL,
                        'title' => $this->title,
                    )
                );

                $body = new View($this->template);
                $body->vars($this->vars);
                $body->show();

                $footer = new View('footer');
                $footer->show();
            }
        }

        $output = ob_get_clean();

        if ($output == '' && $method_output != '') {
            if (is_array($method_output) || is_object($method_output)) {
                $output = json_encode($method_output);
            } else {
                $output = $method_output;
            }
        }

        if (preg_match('/_xhr$/', $method) && $this->json()) {
            header('Content-type: application/json');
            header('Pragma: no-cache');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }
        echo $output;
    }

    final public function __destruct()
    {
        if (method_exists($this, '_destruct')) {
            $this->_destruct();
        }
    }

    public function set_template($template)
    {
        $this->template = $template;
    }

    public function set_title($title)
    {
        $this->title = $title;
    }

    public function __call($method, $args)
    {
        if (preg_match('/^([gs]et)_?([a-zA-Z0-9_\x7f-\xff]*.)$/', $method, $matches)) {
            $method = strtolower($matches[1]);
            $property = strtolower($matches[2]);
            switch ($method) {
                case 'get':
                    return $this->get($property);
                case 'set':
                    return $this->set($property, $args[0]);
            }
        } elseif (preg_match('/^page_(.+)/i', $method, $matches)) {
            $this->xhr = true;
            header('HTTP/1.0 404 Not Found');
            echo '404 page ' . $matches[1] . ' Not Found';
            exit;
        }
    }

    public function __get($var)
    {
        return $this->get($var);
    }

    public function __set($var, $val)
    {
        return $this->set($var, $val);
    }

    public function get($var)
    {
        return (isset($this->vars[strtolower($var)])) ? $this->vars[strtolower($var)] : null;
    }

    public function set($var, $val)
    {
        return $this->vars[strtolower($var)] = $val;
    }

    public function json($do = null)
    {
        if ($do != null) {
            $this->json = (bool)$do;
        }
        return $this->json;
    }

    public function xhr($do = null)
    {
        if ($do != null) {
            $this->xhr = (bool)$do;
        }
        return $this->xhr;
    }
}
