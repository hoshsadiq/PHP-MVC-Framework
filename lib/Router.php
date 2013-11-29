<?php

/**
 * Routing class to match request URL's against given routes and map them to a controller action.
 */
class Router
{
    /**
     * Holds an instance of itself, so that Router can always be called statically
     * @var array
     */
    private static $_instance = null;

    /**
     * Holds the default controller and action
     * @var array
     */
    private $_default = array('controller' => 'index', 'method' => 'index');

    /**
     * Array that holds all Route objects
     * @var array
     */
    private $_routes = array();

    /**
     * Holds the base url
     * @var string
     */
    private $_base = '';

    /**
     * Holds the get values
     * @var string
     */
    private $_get = null;

    /**
     * Holds the post values
     * @var string
     */
    private $_post = null;

    /**
     * Construct the class, simply finds the base and saves it
     * @see $_base
     */
    public function __construct()
    {
        $this->_base = substr(ABSURL, (strlen('http://' . $_SERVER['HTTP_HOST']))) . '/';
    }

    /**
     * Returns the base found during construction
     * @see $_base
     * @return string
     */
    public function get_base()
    {
        return $this->_base;
    }

    /**
     * Route factory method
     *
     * Maps the given URL to the given target.
     * @see $_default
     * @param string $route_url string
     * @param mixed $target The target of this route. Can be as follows
     *    "controllerName/actionName"
     *    array("controllerName", "actionName")
     *    array("controller" => "controllerName", "action" => "actionName")
     * Needless to say, this will fall back to the default params
     * @param array $args Array of optional arguments.
     *    accepts name, filters, and methods
     * @return Router
     */
    public function map($route_url, $target = '', array $args = array())
    {
        $route = new Router_Route();
        $route->url = $this->_base . $route_url;

        if (is_string($target)) {
            $target = explode('/', $target);
        }

        if (!is_array($target) || count($target) != 2) {
            throw new RouterException('Target must be a string of type "controllerName/actionName", array("controllerName", "actionName") or array("controller" => "controllerName", "action" => "actionName")');
        }

        if (!isset($args['name']) || !is_string($args['name'])) {
            throw new RouterException('Router must have an internal name for later reference.');
        }

        if (isset($args['methods'])) {
            $route->methods = $args['methods'];
        }

        if (isset($args['filters'])) {
            foreach ($args['filters'] as $filter => $regex) {
                $route->add_filter($filter, $regex);
            }
        }

        if (!isset($target['controller']) || !isset($target['action'])) {
            if (!isset($target['controller']) && isset($target['action'])) {
                $action = $target['action'];

                unset($target['action']);
                $target = array_values($target);

                $target['controller'] = $target[0];
                $target['action'] = $action;
            } elseif (isset($target['controller']) && !isset($target['action'])) {
                $controller = $target['controller'];

                unset($target['controller']);
                $target = array_values($target);

                $target['controller'] = $controller;
                $target['action'] = $target[0];
            } else {
                $target = array_values($target);
                $target['controller'] = $target[0];
                $target['action'] = $target[1];
            }
        }
        $route->controller = $target['controller'];
        $route->action = $target['action'];

        $route->name = $args['name'];
        $this->_routes[$route->name] = $route;

        return $this;
    }

    /**
     * Initiate the currently requested class
     * @return Router
     */
    public function init()
    {
        $requestMethod = (isset($_POST['_method']) && ($_method = strtoupper($_POST['_method'])) && in_array(
                $_method,
                array('PUT', 'DELETE')
            )) ? $_method : $_SERVER['REQUEST_METHOD'];
        $requestUrl = $_SERVER['REQUEST_URI'];

        // strip GET variables from URL
        if (($pos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $pos);
        }

        $this->matched = $this->match($requestUrl, $requestMethod);

        $class = ucfirst($this->matched->controller) . 'Controller';
        $method = 'page_' . strtolower($this->matched->action);

        if (IS_XHR && method_exists($class, $method . '_xhr')) {
            if (IS_MOBILE && method_exists($class, $method . '_mobile_xhr')) {
                $method .= '_mobile';
            }
            $method .= '_xhr';
        } elseif (IS_MOBILE && method_exists($class, $method . '_mobile')) {
            $method .= '_mobile';
            if (IS_XHR && method_exists($class, $method . '_xhr')) {
                $method .= '_xhr';
            }
        }

        $class = new $class();
        $method_output = $class->_method($method);

        return $this;
    }

    /**
     * Returns the currently matched route
     * Or if $name has been given, returns the route with $name
     * @see init()
     */
    public function get_route($name = null)
    {
        if ($name != null) {
            return isset($this->_routes[$name]) ? $this->_routes[$name] : null;
        }
        if ($this->matched == null) {
            $this->init();
        }

        return $this->matched;
    }

    /**
     * Match given request url and request method and see if a route has been defined for it
     * If so, return route's target
     * @param string $request_url The url requested
     * @param string $allow allowed request types to accept for this route
     * @return Router_Route
     */
    public function match($request_url, $request_method = 'GET')
    {
        foreach ($this->_routes as $route) {
            // compare server request method with route's allowed http methods
            if (!in_array($request_method, $route->methods)) {
                continue;
            }

            // check if request url matches route regex. if not, return false.
            if (!preg_match("@^" . $route->regex . ".*$@i", rtrim($request_url, '/'), $matches)) {
                continue;
            }

            $params = array();

            if (preg_match_all('/:([\w-]+)/', $route->url, $argument_keys)) {
                // grab array with matches
                $argument_keys = $argument_keys[1];

                // loop trough parameter names, store matching value in $params array
                foreach ($argument_keys as $key => $name) {
                    if (isset($matches[$key + 1])) {
                        $params[$name] = $matches[$key + 1];
                    }
                }

            }


            $route->params = $params;

            return $route;
        }
        // return a default route
        $route = new Router_Route();
        $route->url = $_SERVER['REQUEST_URI'];
        $route->controller = $this->_default['controller'];
        $route->action = $this->_default['method'];
        $route->name = 'default';
        $this->_routes[$route->name] = $route;

        return $route;
    }

    /**
     * Returns an instance of this class, new instance will be created if not already
     * @return Router
     */
    public static function instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Reverse route a named route
     *
     * @param string $route_name The name of the route to reverse route.
     * @param array $params Optional array of parameters to use in URL
     * @return string The url to the route
     */
    public function url($route_name, array $params = array())
    {
        // Check if route exists
        if (!isset($this->_routes[$route_name])) {
            if (preg_match('@^([a-z_][a-z0-9_]+)/([a-z_][a-z0-9_]+)$@i', $route_name, $matches)) {
                // return a default route
                $route = new Router_Route();
                $route->url = $_SERVER['REQUEST_URI'];
                $route->controller = $matches[1];
                $route->action = $matches[2];
                $route->name = $route_name;
                $this->_routes[$route->name] = $route;
            } else {
                throw new Exception("No route with the name $route_name has been found.");
            }
        } else {
            $route = $this->_routes[$route_name];
        }
        $url = $route->url;

        // replace route url with given parameters
        if ($params && preg_match_all('/:(\w+)/', $url, $param_keys)) {

            // grab array with matches
            $param_keys = $param_keys[1];

            // loop trough parameter names, store matching value in $params array
            foreach ($param_keys as $i => $key) {
                if (isset($params[$key])) {
                    $url = preg_replace('/:(\w+)/', $params[$key], $url, 1);
                }
            }
        }

        preg_match_all('#/?:\w+/?#', $url, $matches);
        $url = preg_replace('#/?:\w+/?#', '/', $url);
        $url = rtrim('http://' . $_SERVER['HTTP_HOST'] . $url, '/') . '/';

        return $url;
    }

    /**
     * Load the routes from a variable of format
     * '%route_name%' => array(
     *    'route' => 'the/route/to/take',
     *    'target' => 'controller/action',
     *    'methods' => 'determines which types of request methods can go to this route'
     * );
     */
    public function load_var(array $routes)
    {
        foreach ($routes as $name => $data) {
            $route = $data['route'];
            $target = $data['target'];
            unset($data['route'], $data['target']);
            $args = array_merge(array('name' => $name), $data);
            $this->map($route, $target, $args);
        }
    }

    public function is_xhr()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(
            $_SERVER['HTTP_X_REQUESTED_WITH']
        ) == 'xmlhttprequest';
    }

    public function is_mobile()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match(
                '/android|avantgo|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |' .
                'maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|smartphone|nokia|' .
                'sony|sonyericsson|motorola|samsung|palm|treo|smartphone|pre|up\.(browser|link)|vodafone|wap|windows (ce|phone)|' .
                'xda|xiino/i',
                $useragent,
                $matches
            ) ||
            preg_match(
                '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|' .
                'aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|' .
                'capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|' .
                'em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|' .
                'hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|' .
                'idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|' .
                'lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|' .
                'mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|' .
                'n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|' .
                'phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|' .
                'ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|' .
                'sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|' .
                'tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|' .
                'vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|' .
                'your|zeto|zte\-/i',
                $useragent
            )
        ) {
            return !(strtolower($matches[0]) === 'pre' && preg_match('/opera/i', $useragent));
        }
        return false;

        /*
        $is_mobile = false;

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $is_mobile = true;
        }

        if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
            $is_mobile = true;
        }

        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
            'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
            'wapr','webc','winw','winw','xda ','xda-'
        );

        if (in_array($mobile_ua,$mobile_agents)) {
            $is_mobile = true;
        }

        if (strpos(strtolower($_SERVER['ALL_HTTP']),'OperaMini') > 0) {
            $is_mobile = true;
        }

        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'windows') > 0) {
            $is_mobile = false;
        }

        return $is_mobile;*/
    }

//    public function _post($field = null) {
//        if($this->_post == null) {
//            $this->_post = Mysql::escape((object)$_POST, ESCAPE_AUTO, false);
//        }
//        if($field != null) {
//            return $this->_post[$field];
//        }
//        return $this->_post;
//    }
//
//    public function _get($field = null) {
//        if($this->_get == null) {
//            $this->_get = Mysql::escape((object)array_merge($_GET, self::get_route()), ESCAPE_AUTO, false);
//        }
//        if($field != null) {
//            return $this->_get[$field];
//        }
//        return $this->_get;
//    }
}