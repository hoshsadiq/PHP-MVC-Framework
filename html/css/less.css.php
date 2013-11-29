<?php

$file = $_SERVER['QUERY_STRING'];
if (!file_exists($file)) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 Not Found! Sorry</h1>';
    exit();
}

require "lessc.inc.php";
$less = new lessc;
header('Content-type: text/css');
echo $less->compileFile($file);