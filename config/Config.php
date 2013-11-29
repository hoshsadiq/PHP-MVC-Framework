<?php

class CONFIG
{
    const DEBUG = true,
        SALT = '7@S:h{k[32]3${}�]�3$%)"}3~s<a>W',
        AKISMET = '406d2de5ebc9';
}

class COOKIE extends CONFIG
{
    const DOMAIN = 'localhost',
        LENGTH = 3600,
        PATH = '/overheard/',
        SECURE = false,
        HTTPONLY = false,

        DEBUG = parent::DEBUG;
}

