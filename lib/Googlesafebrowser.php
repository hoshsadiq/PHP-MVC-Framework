<?php

/**
 * @author Hosh Sadiq
 * @copyright 2012
 */

define('GOOGLE_KEY', '');

/**
 * Fix for CURLOPT_FOLLOWLOCATION when open basedir or safemode is on.
 * This code was found on on php.net
 * Provided by zsalab - http://www.php.net/manual/en/function.curl-setopt.php#102121
 * @param resource $ch curl resource
 * @param int $maxredirect optional Max number redirects, default: 20
 * @return bool|mixed
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

class Googlesafebrowser
{
    private $_apikey = '';
    private $_client = 'Overheard';
    private $_appver = '1.0';
    private $_pver = '3.0';
    private $_url = 'https://sb-ssl.google.com/safebrowsing/api/lookup';
    private $_single = false;

    public function __construct()
    {
        $this->_apikey = GOOGLE_KEY;
    }

    private function get_url($url = '')
    {
        $params = array(
            'client' => $this->_client,
            'apikey' => $this->_apikey,
            'appver' => $this->_appver,
            'pver' => $this->_pver
        );
        if ($this->is_url($url)) {
            $params['url'] = urlencode($url);
            $this->_single = true;
        } else {
            $this->_single = false;
        }
        $url = $this->_url . '?' . http_build_query($params);
        return $url;
    }

    public function urls_from_text($text)
    {
        preg_match_all(
            '@(?:(?:[a-z]{3,9}://)[a-z0-9\.\-]+|' .
            '(?:www\.)[a-z0-9\.\-]+)(?:(?:\/[\+~%\/\.\w\-]*)?\??' .
            '(?:[\-\+=&;%\@\.\w]*)#?(?:[\.\!\/\\\w]*))?@i',
            $text,
            $matches
        );

        $urls = array_map(
            function ($url) {
                if (!preg_match('@^[a-z]{3,9}://@i', $url)) {
                    $url = 'http://' . $url;
                }
                $url = strip_tags($url);
                return $this->is_url($url) ? $url : '';
            },
            $matches[0]
        );

        $urls = array_unique(array_filter($urls)); // remove duplicates and empty values

        return array_values($urls); // reset the keys and return the urls
    }

    public function is_url($url)
    {
        return !!filter_var($url, FILTER_VALIDATE_URL);
    }

    public function is_safe($text)
    {
        $urls = $this->urls_from_text($text);
        $c_urls = count($urls);

        if ($c_urls == 0) { // no urls found, text is safe
            return true;
        } elseif ($c_urls == 1) { // one url found, check only that url
            return $this->do_single($urls[0]);
        } else {
            return $this->do_multiple($urls);
        }
    }

    public function do_single($url)
    {
        if (!$this->is_url($url)) {
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->get_url($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $output = curl_exec_follow($ch);
        if (curl_errno($ch)) {
            return array(
                'checked' => false,
                'url' => $url,
                'error' => array('errno' => curl_errno($ch), 'errmsg' => curl_error($ch))
            );
        }
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 204) {
            return true;
        } elseif ($http_code == 200) {
            return array(
                'checked' => true,
                'passed' => false,
                'url' => $url,
                'phishing' => stristr($output, 'phishing') !== false,
                'malware' => stristr($output, 'malware') !== false
            );
        } else {
            $errors = array(
                '400' => 'Bad Request - The HTTP request was not correctly formed',
                '401' => 'Not Authorized - The apikey is not authorized',
                '503' => 'Service Unavailable - The server cannot handle the request. Besides the normal server failures, it could also indicate that the client has been "throttled" by sending too many requests'
            );
            return array(
                'checked' => false,
                'error' => $errors[$http_code]
            );
        }
    }

    public function do_multiple($urls)
    {
        if (!is_array($urls)) {
            return null;
        }
        $urls = array_filter($urls, array(&$this, 'is_url'));
        if (count($urls) === 0) {
            return true;
        }

        $c_urls = count($urls);
        array_unshift($urls, ( string )$c_urls);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->get_url());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, implode("\n", $urls));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));

        $output = curl_exec_follow($ch);
        if (curl_errno($ch)) {
            return array(
                'checked' => false,
                'url' => $url,
                'error' => array('errno' => curl_errno($ch), 'errmsg' => curl_error($ch))
            );
        }
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 204) {
            return true;
        } elseif ($http_code == 200) {
            array_shift($urls); // remove the url count
            $results = explode("\n", $output);

            $results = array_map(
                function ($result) {
                    $results = $this->_check_res($result);
                    return ($results['phishing'] || $results['malware']) ? $results : true;
                },
                $results
            );

            $results = array_combine($urls, $results);

            return array(
                'checked' => true,
                'passed' => false,
                'urls' => $results
            );
        } else {
            $errors = array(
                '400' => 'Bad Request - The HTTP request was not correctly formed',
                '401' => 'Not Authorized - The apikey is not authorized',
                '503' => 'Service Unavailable - The server cannot handle the request. Besides the normal server failures, it could also indicate that the client has been "throttled" by sending too many requests'
            );
            return array(
                'checked' => false,
                'error' => $errors[$http_code]
            );
        }
    }

    private function _check_res($result)
    {
        return array(
            'phishing' => stristr($result, 'phishing') !== false,
            'malware' => stristr($result, 'malware') !== false
        );
    }
}

$google = new Googlesafebrowser();
var_dump($google->do_multiple(array('http://www.google.com', 'http://www.ianfette.org')));

?>