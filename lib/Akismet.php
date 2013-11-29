<?php

/**
 * A simple Akismet interaction class
 *
 * @package Overheard
 * @subpackage Akismet
 * @author Hosh Sadiq
 * @version 1
 * @date 2012-06-24
 * @todo Add statistics?
 */
final class Akismet
{
    /**
     * Holds the Akismet server host
     * @var string Akismet server host
     */
    private static $_server = 'rest.akismet.com';

    /**
     * Holds the akismet version url
     * @var string Akismet version
     */
    private static $_version = '1.1';

    /**
     * Holds the Akismet API key
     * @var string Akismet API key
     */
    private static $_key = '';

    /**
     * Holds any errors generated
     * @var string Error message
     */
    private static $error = '';

    /**
     * Static only class
     */
    public function __construct()
    {
        throw new AkismetException('Akismet class cannot be instantiated');
    }

    /**
     * Create a new Akismet handler
     * @param string $key Akismet API key
     */
    public static function init()
    {
        if (self::$_key != '') {
            return;
        }

        Maker::def('AKISMET_VERIFY', 'verify-key');
        Maker::def('AKISMET_CHECK', 'comment-check');
        Maker::def('AKISMET_SPAM', 'submit-spam');
        Maker::def('AKISMET_HAM', 'submit-ham');

        self::$_key = CONFIG::AKISMET;
        if (!self::_valid()) {
            throw new AkismetException('Invalid key given! Please try again');
        }
    }

    /**
     * Check a comment against Akismet network to see if it is spam or not
     * @param array $vars Information about the comment, see Akismet::_vars() for values
     * @return boolean True if it's spam, false if not
     */
    public static function check($vars)
    {
        if (!self::_valid()) {
            return false;
        }
        $result = self::_send(AKISMET_SPAM, self::_vars($vars));
        return $result != 'false';
    }

    /**
     * Check only content, this is useful for anonymous forms without author fields (e.g. just content)
     * It is not possible to add extra args, as such, IP, referrer etc will revert to their default values
     * @param array $vars Information about the comment, see Akismet::_vars() for values
     * @return boolean True if it's spam, false if not
     */
    public static function content($content, $type = 'post')
    {
        return self::check(
            [
                'content' => $content,
                'type' => $type
            ]
        );
    }

    /**
     * Mark a message as spam (missed spam)
     * @param array $vars Information about the comment, see Akismet::_vars() for values
     * @return boolean True on success
     */
    public static function spam($vars)
    {
        return self::_send(AKISMET_SPAM, self::_vars($vars));
    }

    /**
     * Mark the message as ham (false positive)
     * @param array $vars Information about the comment, see Akismet::_vars() for values
     * @return boolean True on success
     */
    public static function ham($vars)
    {
        return self::_send(AKISMET_HAM, self::_vars($vars));
    }

    /**
     * Check if the API key provided is valid or not
     * @return boolean True if key is valid, false otherwise
     */
    private static function _valid()
    {
        return self::_send() == 'valid';
    }

    /**
     * Validates variables to make sure the required ones are present and adds non-akismet default variables
     * Only some are required by the API, but these revert back to default values.
     * @param array $vars Information about the message
     * This array can contain the following:
     * user_ip|ip string The user's IP address, defaults to the contents of $_SERVER['REMOTE_ADDR']
     * user_agent|agent string The user agent of the poster defaults to the contents of $_SERVER['HTTP_USER_AGENT']
     * referrer string The content of the HTTP_REFERER header should be sent here. Defaults to contents of $_SERVER['HTTP_REFERER']
     * permalink string The permanent location of the entry the comment was submitted to.
     * comment_type|type string May be blank, comment, trackback, pingback, or a made up value like "registration".
     * comment_author|author string Name submitted with the comment
     * comment_author_email|author_email|email string Email address submitted with the comment
     * comment_author_url|author_url|url string URL submitted with comment
     * comment_content|content|comment string The content that was submitted.
     * @return array The parameters allowed by Akismet
     */
    private static function _vars($vars)
    {
        if (!isset($vars['user_ip'])) {
            if (isset($vars['ip'])) {
                $vars['user_ip'] = $vars['ip'];
                unset($vars['ip']);
            } else {
                $vars['user_ip'] = $_SERVER['REMOTE_ADDR'];
            }
        }
        if (!isset($vars['user_agent'])) {
            if (isset($vars['agent'])) {
                $vars['user_agent'] = $vars['agent'];
                unset($vars['agent']);
            } else {
                $vars['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            }
        }
        if (!isset($vars['comment_type'])) {
            if (isset($vars['type'])) {
                $vars['comment_type'] = $vars['type'];
                unset($vars['type']);
            }
        }
        if (!isset($vars['comment_author'])) {
            if (isset($vars['author'])) {
                $vars['comment_author'] = $vars['author'];
                unset($vars['author']);
            }
        }
        if (!isset($vars['comment_author_email'])) {
            if (isset($vars['email'])) {
                $vars['comment_author_email'] = $vars['email'];
                unset($vars['email']);
            } elseif (isset($vars['author_email'])) {
                $vars['comment_author_email'] = $vars['author_email'];
                unset($vars['author_email']);
            }
        }
        if (!isset($vars['comment_author_url'])) {
            if (isset($vars['url'])) {
                $vars['comment_author_url'] = $vars['url'];
                unset($vars['url']);
            } elseif (isset($vars['author_url'])) {
                $vars['comment_author_url'] = $vars['author_url'];
                unset($vars['author_url']);
            }
        }
        if (!isset($vars['comment_content'])) {
            if (isset($vars['content'])) {
                $vars['comment_content'] = $vars['content'];
                unset($vars['content']);
            } elseif (isset($vars['comment'])) {
                $vars['comment_content'] = $vars['comment'];
                unset($vars['comment']);
            }
        }
        if (!isset($vars['referrer'])) {
            $vars['referrer'] = $_SERVER['HTTP_REFERER'];
        }

        return array_intersect_key(
            $vars,
            array_flip(
                array(
                    'user_ip',
                    'user_agent',
                    'referrer',
                    'permalink',
                    'comment_type',
                    'comment_author',
                    'comment_author_email',
                    'comment_author_url',
                    'comment_content'
                )
            )
        );
    }

    /**
     * Make an akismet request, if no arguments are given, it will check that the API key is valid
     * @param string $type Type of request, can only be AKISMET_VERIFY|AKISMET_CHECK|AKISMET_SPAM|AKISMET_HAM, default: AKISMET_VERIFY
     * @param array $args Arguments to send to the akismet server, see Akismet::_vars()
     * @return string Returns the server response.
     */
    private static function _send($type = AKISMET_VERIFY, $args = array())
    {
        $args['blog'] = ABSURL;

        $allowed = array(AKISMET_VERIFY, AKISMET_CHECK, AKISMET_SPAM, AKISMET_HAM);
        if (!in_array($type, $allowed)) {
            self::$error = 'Invalid Akismet type given: ' . $type . ', allowed values: AKISMET_VERIFY|AKISMET_CHECK|AKISMET_SPAM|AKISMET_HAM';
            return false;
        }

        if ($type == AKISMET_VERIFY) {
            $host = self::$_server;
            $args['key'] = CONFIG::AKISMET;
        } else {
            $host = CONFIG::AKISMET . '.' . self::$_server;
        }

        $url = 'http://' . $host . '/' . self::$_version . '/' . $type;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Overheard/1.0 | Overheard-Akismet/1.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ' . $host));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'key=' . $args['key'] . '&blog=' . rawurlencode($args['blog']));

        $output = curl_exec_follow($ch);
        if (curl_errno($ch)) {
            self::$error = 'cURL error: ' . curl_errno($ch) . ': ' . curl_error($ch);
            return false;
        }
        curl_close($ch);

        return $output;
    }
}

?>