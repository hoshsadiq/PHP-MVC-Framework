<?php

/**
 * A simple authorisation class, this must be used in conjunction with the User class
 * The resources are selected from the `user_permissions` table
 * @author Hosh
 */
class Auth
{
    /**
     * Constructor does nothing
     */
    public function __construct()
    {
    }

    /**
     * Checks if the user is allowed a specific resource
     * @param string $resource The resource
     * @param string $user_id The user to check this for
     * @return boolean Whether or not the user is allowed it
     */
    public static function user_has($resource, $user_id = null)
    {
        if ($user_id == null) {
            $user = Maker::get('logged_in_user');
            if ($user == false) {
                return self::guest_has($resource);
            }
            $user_id = $user->id;
        }
        $query = Mysql::query(
            'SELECT `access` FROM `user_permissions` WHERE `user_id`=%u AND `resource`=%s',
            $user_id,
            $resource
        );
        if ($query->count == 0) {
            $role_id = Mysql::query('SELECT `role_id` FROM `users` WHERE `id`=%u', $user_id)->var;
            return self::role_has($resource, $role_id);
        }
        //return $query->row->access == 'allow';
        return $query->var == 'allow';
    }

    /**
     * Checks if a user role is allowed a specific resource
     * @param string $resource The resource
     * @param int $role_id The role to check this for
     * @return boolean Whether or not the role is allowed this
     */
    public static function role_has($resource, $role_id = null)
    {
        if ($role_id == null) {
            $user = Maker::get('logged_in_user');
            if ($user == false) {
                return self::guest_has($resource);
            }
            $role_id = $user->role_id;
        }
        $query = Mysql::query(
            'SELECT `access` FROM `user_permissions` WHERE `role_id`=%u AND `resource`=%s',
            $role_id,
            $resource
        );
        if ($query->count == 0) {
            return false;
        }
        //return $query->row->access == 'allow';
        return $query->var == 'allow';
    }

    /**
     * Checks if a guest is allowed a resource
     * @param string $resource The resource
     * @return boolean Whether or not the user is allowed the resource
     */
    public static function guest_has($resource)
    {
        $query = Mysql::query(
            'SELECT p.access FROM `user_roles` AS r LEFT JOIN user_permissions AS p ON r.id=p.role_id WHERE r.name=\'Guest\' AND r.editable=0 AND p.resource=%s',
            $resource
        );
        if ($query->count == 0) {
            return false;
        }
        //return $query->row->access == 'allow';
        return $query->var == 'allow';
    }

    public static function get_default()
    {
        return Mysql::query('SELECT `id` FROM `user_roles` WHERE `name`=%s', 'Member')->var;
    }
}
