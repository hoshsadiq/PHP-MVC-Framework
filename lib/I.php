<?php

final class I
{
    private $gets = array();

    private function setGets()
    {
        $request = Router::get_req();
        $gets = substr(substr($request, 0, strpos('/') + 1), 0, strpos('/') + 1); // remove controller and method
        for ($i = 0, $c_gets = count($gets); $i < $c_gets; $i++) {

        }
    }

    public function g($param)
    {
        return isset($_GET[$param]) ? trim($_GET[$param]) : '';
    }

    public function p($param)
    {
        return isset($_POST[$param]) ? trim($_POST[$param]) : '';
    }

    public function pg($param)
    {
        return $ret = self::p($param) == '' ? self::g($param) : $ret;
    }
}

