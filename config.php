<?php

class CONFIG
{
    const DEBUG = false,
        SALT = '7@S:h{k[32]3${}]3$%)"}3~sdsa>W',
        BASE = 'http://localhost/overheard/';
}

class DB_CONFIG extends CONFIG
{
    const HOST = 'localhost',
        USER = 'root',
        PASS = '',
        NAME = 'overheard',
        PREFIX = 'ovahrd_',

        DEBUG = parent::DEBUG,
        DEBUG_LOG = 'sql_log.txt';
}

class COOKIE_CONFIG extends CONFIG
{
    const DOMAIN = 'localhost',
        LENGTH = 3600,
        PATH = '/overheard/',
        SECURE = false,
        HTTPONLY = false,

        DEBUG = parent::DEBUG;
}

class TPL_CONFIG extends CONFIG
{
    const ROOT = './html/',
        CACHE = false, //'./cache/',
        BOOLS_TRUE = 'true|yes|y|t|on|1',
        BOOLS_FALSE = 'false|no|n|f|off|0',
        PARSE_PHP = false,
        COMPRESS = false, // facks up, don't use, leave at false otherwise you're gay! :) FEGIT

        /**
         * Define constants in the tpl files or assume they're all defined variables
         */
        USE_CONSTANTS = true,

        /**
         * Define config constants in the tpl files or assume they're all defined variables
         * This can ONLY be true if USE_CONSTANTS is also true
         */
        USE_CLASS_CONSTANTS = true,

        /**
         * Contains information on what to do with unknown variables
         * 0 = Remove unknown variables
         * 1 = Replace the variable with a HTML comment, highly unrecommended due to input fields, unless during development
         * 2 = Keep the variable shown (with the curly braces)
         */
        UNKNOWNS = 0,

        DEBUG = parent::DEBUG;
}

