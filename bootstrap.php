<?php

define('ABSPATH', dirname(__FILE__));
define('ABSURL', 'http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . substr(
        $_SERVER['PHP_SELF'],
        0,
        -10
    ));

define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
define('PE', PHP_EOL);

set_include_path(
    ABSPATH . DS . 'lib' . DS . PS .
    ABSPATH . DS . 'lib' . DS . 'Core' . DS . PS .
    ABSPATH . DS . 'Config' . DS . PS .
    ABSPATH . DS . 'app' . DS . PS .
    ABSPATH . DS . 'model' . DS
);

@include_once('FirePHPCore/fb.php');
require_once('Maker.php');
Maker::init();

include_once('Routes.php');
$router = Router::instance();
$router->load_var($routes);
$router->init();
exit;