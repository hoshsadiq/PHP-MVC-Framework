<?php

/** highest priority first! **/
// @todo output method (ex html, json, txt etc)
// @todo callback functions, before and after

$routes = array(
    'user/login' => array(
        'route' => 'login',
        'target' => 'user/login',
        'methods' => 'GET,POST'
    ),
    'user/register' => array(
        'route' => 'register',
        'target' => 'user/register',
        'methods' => 'GET,POST'
    ),
    'user/logout' => array(
        'route' => 'logout',
        'target' => 'user/logout'
    ),
    'content/submit' => array(
        'route' => 'submit',
        'target' => 'content/submit'
    ),
    'content/getpost' => array(
        'route' => 'getpost',
        'target' => 'content/getpost',
    ),
    'content/search' => array(
        'route' => 'search',
        'target' => 'content/search',
    ),
    'content/vote' => array(
        'route' => 'vote/:type/:id',
        'target' => 'content/vote',
        'methods' => 'POST',
        'filters' => [
            'type' => 'up|down',
            'id' => '[0-9]+'
        ]
    ),
    'index/index/page' => array(
        'route' => ':location/page/:page',
        'target' => 'index/index',
        'methods' => 'GET',
        'filters' => [
            'location' => '[a-zA-Z_\-]+',
            'page' => '[0-9]+'
        ]
    ),
    'index/index' => array(
        'route' => ':location',
        'target' => 'index/index',
        'methods' => 'GET',
        'filters' => [
            'location' => '[a-zA-Z_\-]+'
        ]
    ),
    /*
    'name_for_url_gen' => array(
        'route' => 'the_route_to_replace', // e.g. login, variables can be set through :var_name
        'target' => 'the_target_controller/target_action', // e.g. user/login will run UserController::page_login()
        'methods' => '', // array or comma delimeted string, can have one or more of: GET, POST, PUT, DELETE, default sets to all
        'filter' => array('var_name' => 'regex_filter') // A regular expression to apply to :var_name, regex_filter may also be: int|integer|str|string|bool|boolean
    )*/
);