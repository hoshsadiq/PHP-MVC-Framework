<?php

/**
 * @author TheHosh, Simon Stac
 * @copyright 2012
 */

/**
 * used for future for i18n
 */
function __($var)
{
    $args = func_get_args();
    array_shift($args);
    return vsprintf($var, $args);
}

function _e()
{
    echo call_user_func_array('__', func_get_args());
}

function url($route_name, array $args = array())
{
    return Router::instance()->url($route_name, $args);
}

/**
 * Get a variable from either $_GET or $_POST.
 * $_POST takes priority.
 * @param string $var The variable to get.
 * @return mixed The value of the variable.
 */
function _get($var, $type = 'auto')
{
    if (is_array($var)) {
        $get = $var[0];
        $type = $var[1];
    } else {
        $get = $var;
    }
    $ret = '';
    if (isset($_POST[$get]) && $_POST[$get] != '') {
        $ret = $_POST[$get];
    } elseif (isset($_GET[$get]) && $_GET[$get] != '') {
        $ret = $_GET[$get];
    }

    switch ($type) {
        case 'int':
        case 'integer':
            return (integer)$ret;
            break;
        case 'mysql':
            return mysql::val($ret);
            break;
        case 'float':
        case 'double':
        case 'real':
            return (float)$ret;
            break;
        case 'bool':
        case 'boolean':
            return (bool)$ret;
            break;
        case 'string':
        case 'str':
        default:
            return (string)$ret;
    }
}

/**
 * If no arguments are given, all elements within $_POST and $_GET will be turned into variables, this way is highly unrecommended
 * This function depends on the function _get()
 * @param bool $return_array True to return array. If this is used, this HAS to be the only argument
 * @param string $string,... The strings to $_POST or $_GET keys to make a variable
 * @return array Array of $_POST and $_GET values. This only happens when there is only one argument set to true
 * @see _get()
 */
function _fields()
{
    $a = func_get_args();
    if (count($a) == 0) {
        $fields = array_merge($_GET, $_POST);
        foreach ($fields as $key => $val) {
            _fields($key);
        }
    } elseif ($a[0] === true) {
        return array_merge($_GET, $_POST);
    } else {
        foreach ($a as $arr) {
            if (is_array($arr)) {
                $var = $arr[0];
            } else {
                $var = $arr;
            }
            global $$var;
            $$var = _get($arr);
        }
    }
}

/**
 * Fix for CURLOPT_FOLLOWLOCATION when open basedir or safemode is on.
 * This code was found on on php.net
 * Provided by zsalab - http://www.php.net/manual/en/function.curl-setopt.php#102121
 * @param resource $ch curl resource
 * @param int $maxredirect optional Max number redirects, default: 20
 */
function curl_exec_follow($ch, &$maxredirect = null)
{
    $mr = $maxredirect === null ? 20 : ( int )$maxredirect;

    if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
    } else {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        if ($mr > 0) {
            $newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

            $rch = curl_copy_handle($ch);
            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
            do {
                curl_setopt($rch, CURLOPT_URL, $newurl);
                $header = curl_exec($rch);
                if (curl_errno($rch)) {
                    $code = 0;
                } else {
                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                    if ($code == 301 || $code == 302) {
                        preg_match('/Location:(.*?)\n/', $header, $matches);
                        $newurl = trim(array_pop($matches));
                    } else {
                        $code = 0;
                    }
                }
            } while ($code && --$mr);
            curl_close($rch);
            if (!$mr) {
                if ($maxredirect === null) {
                    trigger_error(
                        'Too many redirects. When following redirects, libcurl hit the maximum amount.',
                        E_USER_WARNING
                    );
                } else {
                    $maxredirect = 0;
                }
                return false;
            }
            curl_setopt($ch, CURLOPT_URL, $newurl);
        }
    }
    return curl_exec($ch);
}


/**
 * Debug a variable
 * @param mixed $var The variable to debug.
 * @param bool $exit False will prevent script exit after debugging.
 * @return string Formatted data about the variable.
 */
function debug($var, $exit = true)
{
    echo '<pre class="debug system">';
    var_dump($var);
    echo '</pre>';
    if ($exit == true) {
        exit;
    }
}

/**
 * Prepare a string for output, so it doesn't inject js and doesn't break any html
 * This function is mostly used to print() user input
 * @param string $str Dirty string
 * @return string Sanitized UTF-8 string
 */
function html($str)
{
    $str = nl2br(htmlspecialchars($str, ENT_COMPAT, 'UTF-8'));
    $str = preg_replace('#(<br />\s*|<br>\s*){2,}#', '<br /><br />', $str);
    return $str;
}

/**
 * Remove not allowed data before storage
 * @param string $str Dirty string
 * @return string Sanitized UTF-8 string
 */
function safe_content($str)
{
    $str = preg_replace('#(<br />\s*|<br>\s*){2,}#', '<br /><br />', $str);
    return $str;
}

/**
 * Display time in human-readable form
 * @param int $time Unix timestamp
 * @return string Readable time/date
 */
function when($time)
{
    // how many seconds ago
    $ago = time() - $time;

    // 0sec - 1min
    if ($ago < 60) {
        $when = __('less than a minute ago');
    } // 1min - 1hr
    elseif ($ago < 60 * 60) {
        $m = round($ago / 60, 0);

        // pluralize appropriately
        $when = __($m . ($m > 1 ? ' minutes' : ' minute') . ' ago');
    } // 1hr - 1.5hr
    elseif ($ago < 60 * 60 * 1.5) {
        $when = __('an hour ago');
    } // 1.5hrs - 24hrs (start rounding up from this point because approximation is better than precision here)
    elseif ($ago < 60 * 60 * 23.5) {
        $h = round($ago / 60 / 60, 0);
        $when = __($h . ' hours ago');
    } // 24hr - 36hr
    elseif ($ago < 60 * 60 * 36) {
        $when = __('a day ago');
    } // 36hr - 7days
    elseif ($ago < 60 * 60 * 24 * 7) {
        $d = round($ago / 60 / 60 / 24, 0);
        $when = __($d . ' days ago');
    } // 7days - 4weeks
    elseif ($ago < 60 * 60 * 24 * 7 * 4) {
        $d = round($ago / 60 / 60 / 24 / 7, 0);
        $when = __($d . ' weeks ago');
    } // 4weeks - 44days (~1.5month)
    elseif ($ago < 60 * 60 * 24 * 44) {
        $when = __('a month ago');
    } // 1.5months - 11months
    elseif ($ago < 60 * 60 * 24 * 30.5 * 11) {
        $m = round($ago / 60 / 60 / 24 / 30.5, 0);
        $when = __($m . ' months ago');
    } // 11 months - 14 months
    elseif ($ago < 60 * 60 * 24 * 30.5 * 14) {
        $when = __('a year ago');
    } // just show "Month 2012"
    else {
        $when = __(date('M Y', $time));
    }

    return $when;
}


/**
 * Determine whether given string is a valid location name
 * @param string $location
 * @return bool
 */
function is_location($location)
{
    if (preg_match('/^[a-z, ()]+$/i', $location)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Validates empty or completely associative arrays
 * @param array $arr Array to validate
 * @return bool
 */
function is_assoc($arr)
{
    return (is_array($arr) && count(array_filter(array_keys($arr), 'is_string')) == count($arr));
}

/**
 * @return bool
 * @see http://stackoverflow.com/questions/3788369/how-to-tell-if-a-session-is-active/7656468#7656468
 */
function session_active()
{
    $setting = 'session.use_trans_sid';
    $current = ini_get($setting);
    if (false === $current) {
        throw new Exception(sprintf('Setting %s does not exists.', $setting));
    }
    $result = @ini_set($setting, $current);
    return $result !== $current;
}

function pagination($max_pages, $page, $route, $params)
{
    $curpage = $page + 1;
    if ($max_pages < 15) {
        $gen_pages = range(1, $max_pages);
    } else {
        $gen_pages = range(1, 3);
        $gen_pages[] = '...';
        $mid_6 = (int)ceil($max_pages / 2);
        $gen_pages = array_merge($gen_pages, range($mid_6 - 3, $mid_6 + 3), ['...'], range($max_pages - 3, $max_pages));
    }
    $pages = [];
    // if($curpage-1 >= 1) {
    array_unshift($gen_pages, 0);
    //  }
    //  if($curpage != $max_pages) {
    $gen_pages[] = -1;
    //  }
    $total = count($gen_pages);
    foreach ($gen_pages as $k => $i) {
        if ($i === '...') {
            $pages[] = (object)[
                'url' => url(
                        $route . ($page_url == 1 ? '' : '/page'),
                        array_merge($params, ['page' => $curpage])
                    ) . '#',
                'page' => '...',
                'current' => false,
                'classes' => 'nav-dots disabled'
            ];
            continue;
        }

        $classes = [];

        if ($k == 0) {
            $classes[] = 'first';
        }
        $classes[] = 'nav-item';
        if ($i == $curpage) {
            $classes[] = 'active';
        }
        if ($i === 0) {
            $classes[] = 'previous';
        }
        if ($i === -1) {
            $classes[] = 'next';
        }
        if ((int)($k + 1) === (int)$total) {
            $classes[] = 'last';
        }

        if ($i === 0) {
            $page_num = 'Previous';
            $page_url = $curpage - 1;
        } elseif ($i === -1) {
            $page_num = 'Next';
            $page_url = $curpage + 1;
        } else {
            $page_num = $i;
            $page_url = $i;
        }
        if ($page_url == 0 || $page_url >= $max_pages) {
            $classes[] = 'disabled';
        }

        if (in_array('active', $classes) || in_array('disabled', $classes)) {
            $url = url($route, array_merge($params)) . '#';
        } else {
            $url = url($route . ($page_url == 1 ? '' : '/page'), array_merge($params, ['page' => $page_url]));
        }

        $pages[] = (object)[
            'url' => $url,
            'page' => $page_num,
            'current' => $i - 1 == $page,
            'classes' => join(' ', $classes)
        ];
    }
    return $pages;
}

/**
 * Error reporting. Accepts "string" or a serialized array() of errors
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 * @todo adapt to overheard
 */
/*function user_warning($errno, $errstr, $errfile, $errline)
{
    global $tpl;

    $data = @unserialize($errstr);
    // it's a serialized array of errors
    if($data !== false && is_array($data)) {
        if(count($data) > 1) {
            $errstr = '<ul><li>' . implode('</li><li>', $data) . '</li></ul>';
        }
        else {
            $errstr = $data[0];
        }
    }

    $tpl->set('U_ERROR', '<div class="error">' . $errstr . '</div>');
}*/

// http://www.php.net/manual/en/function.vsprintf.php#87031
function vnsprintf(string $format, array $data)
{
    preg_match_all(
        '/ (?<!%) % ( (?: [[:alpha:]_-][[:alnum:]_-]* | ([-+])? [0-9]+ (?(2) (?:\.[0-9]+)? | \.[0-9]+ ) ) ) \$ [-+]? \'? .? -? [0-9]* (\.[0-9]+)? \w/x',
        $format,
        $match,
        PREG_SET_ORDER | PREG_OFFSET_CAPTURE
    );
    $offset = 0;
    $keys = array_keys($data);
    foreach ($match as &$value) {
        if (($key = array_search($value[1][0], $keys, true)) !== false || (is_numeric(
                    $value[1][0]
                ) && ($key = array_search((int)$value[1][0], $keys, true)) !== false)
        ) {
            $len = strlen($value[1][0]);
            $format = substr_replace($format, 1 + $key, $offset + $value[1][1], $len);
            $offset -= $len - strlen(1 + $key);
        }
    }
    return vsprintf($format, $data);
}

// args
function _sprintf(string $format, array $data)
{
    $args = func_get_args();
    $format = array_shift($args);
    if (count($args) > 0) {
        if (count($args) == 1) {
            $args = $args[0];
            if (!is_array($args)) {
                $args = array($args);
            }
        }
    } else {
        return false;
    }
    return vnsprintf($format, $args);
}

function object_to_array(stdClass $class)
{
    $class = (array)$class;

    foreach ($class as $key => $value) {
        if (is_object($value) && get_class($value) === 'stdClass') {
            $class[$key] = object_to_array($value);
        }
    }
    return $class;
}

// easier for views
function user_can($resource, $user_id = null)
{
    return Auth::user_has($resource, $user_id);
}

function redirect($url)
{
    $junk = ob_get_clean();
    Maker::set('empty_session', false);
    header('Location: ' . $url);
    exit;
}

/**
 * Checks whether an integer is within a range OR is one of the arguments.
 * If three arguments given, it will assume that we want to check it for a range.
 * First argument can be an integer to check, or an array of options:
 *   * var is the integer check
 *   * start is whether to do a >= check or > for the start range
 *     * This can be true, 'gte', 'ge' or '>='
 *     * Anything else is assumed to check on >
 *     * Defaults to true
 *     * Ignored if it's not a range check
 *   * end is whether to do a <= check or < for the end.
 *     * This can be true, 'lte', 'le' or '<='
 *     * Anything else is assumed to check on <
 *     * Defaults to true
 *     * Ignored if it's not a range check
 *   * individual decides whether to check $min and $max as individual ints or not
 *
 * @example in_range(1, 1, 2); // returns true
 * @example in_range(1, 10, 12); // returns false
 * @example in_range(1, 2, 3, 1); // returns true
 * @example in_range(1, 2, 3, 52); // returns false
 * @example
 * $options = array('var' => 1, 'start' => '>=', 'end' => '<=');
 * in_range($options, 2, 3); // returns false
 * @example
 * if(in_range(array('var' => 1, 'start' => true, 'end' => false), 1, 4)) {
 *     echo 'Integer 1 is in range of 1 through 4 with using >= and <';
 * } else {
 *     echo 'Integer 1 is in not range of 1 through 4 with using >= and <';
 * }
 * // outpuits: Integer 1 is in range of 1 through 4 with using >= and <
 * @var int|array $args The integer to check or array of options (see above)
 * @var int $start The start of the range or integer to check against
 * @var int $end The end of the range or integer to check against
 * @var optional other integers to check against.
 */
function in_range($args, $min, $max)
{
    $defaults = array(
        'var' => '',
        'start' => $min,
        'end' => $max,
        'individual' => false
    );

    if (!is_array($args)) {
        $args = array('var' => $args);
    }
    $args = array_merge($defaults, $args);

    if (!is_int($args['var'])) {
        return false;
    }

    if (func_num_args() === 3 && !$args['individual']) {
        $in_range = false;
        if ($args['start'] == 'gte' || $args['start'] == 'ge' || $args['start'] == '>=') {
            $in_range = $args['var'] >= $min;
        } else {
            $in_range = $args['var'] > $min;
        }
        if ($args['end'] == 'lte' || $args['end'] == 'le' || $args['end'] == '<=') {
            $in_range = $in_range && $args['var'] <= $min;
        } else {
            $in_range = $in_range && $args['var'] < $min;
        }
        return $in_range;
    } else {
        $argv = func_get_args();
        $argc = func_num_args();

        // starting at 1 skips $args
        for ($i = 1; $i < $argc; $i++) {
            if ($args['var'] !== $argv[$i]) {
                return false;
            }
        }
        return true;
    }
    return false;
}