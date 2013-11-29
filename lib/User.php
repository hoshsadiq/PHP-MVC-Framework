<?php

class User extends Object
{
    /**
     * Log a user in
     *
     * @param string $user Username or email of user
     * @param string $pass Password
     * @param bool $load Return the userdata or not.
     * @return bool
     */
    public static function login($user, $pass)
    {
        if (strtolower($user) == 'anonymous') {
            return !self::loggedin();
        }
        $saltedpass = Maker::salt($pass);

        if (!Valid::email($user) && Valid::username($user)) {
            $field = 'username';
        } else {
            if (Valid::email($user)) {
                $field = 'email';
            } else {
                Session::error('Please enter a valid email or username');
                return false;
            }
        }

        $query = Mysql::query(
            'SELECT `id`, `' . $field . '`, `password` FROM `users` WHERE `' . $field . '`=%s LIMIT 1;',
            $user
        );

        if ($query->rows == 0) {
            Session::error('This user is not registered');
            return false;
        }

        $data = $query->row;

        if ($user == $data->{$field}) {
            if ($saltedpass != $data->password) {
                Session::error('Wrong password');
            }

            if (Session::error_count() > 0) {
                return false;
            }
            $sessid = Maker::rnd_str();
            Mysql::query(
                'INSERT INTO `sessions` SET `password`=%s, sessid=%s, userid=%u, start=%u, ip=%b',
                $saltedpass,
                $sessid,
                $data->id,
                time(),
                ip()->realip
            );

            Session::instance()->username = $user;
            Session::instance()->password = $sessid;
            Session::instance()->userid = $data->id;
            return self::loggedin();
        }
    }

    /**
     * Check if user is logged in
     *
     * @return bool True if logged in, False if anonymous
     */
    public static function loggedin()
    {
        if (Maker::get('logged_in_user') != null) {
            return Maker::get('logged_in_user');
        }

        $query = Mysql::query(
            'SELECT u.* FROM sessions s LEFT JOIN users u ON s.userid=u.id
                                           WHERE s.sessid=%s AND s.password=u.password AND u.username=%s AND s.userid=%u AND s.ip=%b',
            Session::instance()->password,
            Session::instance()->username,
            Session::instance()->userid,
            ip()->realip
        );

        if ($query->rows == 1) {
            Maker::set('logged_in_user', $query->row);
            return true;
        }
        Maker::set('logged_in_user', null);
        return false;
    }

    /**
     * Register a new user, all will be validated
     *
     * @param string $username
     * @param string $password
     * @param string $password_confirm
     * @param string $email
     * @return int|false ID of newly registered user on success, false on error
     */
    public static function register($username, $password, $password_confirm, $email)
    {
        // salt and md5 password
        $password = Maker::salt($password, true);
        $password_confirm = Maker::salt($password_confirm, true);

        if (!Valid::username($username)) {
            Session::error(
                __(
                    'Usernames must be between 6 and 25 characters and can only contain A-Z, 0-9, underscore (_) and full stop.'
                )
            );
        } elseif (User::get_by('username', $username, false) != null) {
            Session::error(__('This username has already been taken.'));
        }

        if (!Valid::password($password)) {
            Session::error(__('Password must be between 5 and 50 characters.'));
        } elseif ($password != $password_confirm) {
            Session::error(__('Passwords don\'t match.'));
        }

        if (!Valid::email($email)) {
            Session::error(__('A valid email must be given.'));
        } elseif (self::get_by('email', $email, false) != null) {
            Session::error(__('A user has already been registered with this email, if this is you, you can login.'));
        }

        if (Session::error_count() == 0) {
            $role_id = Auth::get_default();
            return Mysql::query(
                'INSERT INTO users (username, password, email, role_id, registered)
                                    VALUES (%s, %s, %s, %u, %u)',
                $username,
                $password,
                $email,
                $role_id,
                time()
            )->insertid;
        }
        return false;
    }

    /**
     * Alias for Auth->get_user_by('id', $id);
     * @param int $id The user id to get, if set to 0, the current logged in user is retrieved, or null if no one is logged in or no matches are found
     * @return object|null
     * @see Auth::get_user_by
     */
    public static function get($id = null)
    {
        return self::get_by('id', $id);
    }

    /**
     * @param string $field The field to match $value against. Allowed values: id, username, email
     * @param mixed $value The value to test the $field on. If set to null, it will use the logged in user's data. If nothing was found, null is returned
     * @param bool $check_login If set to false, the function will not check logged in user details
     * @return object|null
     */
    public static function get_by($field, $value = null, $check_login = true)
    {
        $field = strtolower(preg_replace('/[^a-z]/i', '', $field));
        if (!in_array($field, ['id', 'username', 'email'])) {
            throw new AuthException('Auth::get_user_by() requires argument 1 to be id, username or email');
        }

        if ($value == null) {
            if ($check_login && self::loggedin()) {
                return Maker::get('logged_in_user');
            } else {
                return null;
            }
        }

        $query = Mysql::query('SELECT * FROM `users` WHERE `' . $field . '`=%s', $value);
        if ($query->count == 0) {
            return null;
        }
        return $query->row;
    }
}