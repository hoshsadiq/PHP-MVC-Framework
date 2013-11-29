<?php

class Post
{
    private $cache = [];

    public static function get_all($args = [])
    {
        $sql = ['SELECT posts.id FROM posts'];
        if (count($args) > 0) {
            if (isset($args['orderby'])) {
                $sql[] = 'ORDER BY ' . $args['orderby'];
            }
            if (isset($args['start']) && isset($args['end'])) {
                $sql[] = 'LIMIT ' . $args['start'] . ', ' . $args['end'];
            } elseif (isset($args['end'])) {
                $sql[] = 'LIMIT ' . $args['end'];
            }
        }
        return Mysql::query('SELECT id FROM posts ORDER BY posts.time DESC LIMIT %u, %u', $start, $limit)->results;
    }

    public static function get_by($field, $value)
    {
        $field = strtolower(preg_replace('/[^a-z]/i', '', $field));
        if (!in_array($field, ['id', 'userid', 'user_id', 'location', 'longlat'])) {
            throw new AuthException('Auth::get_user_by() requires argument 1 to be id, username or email');
        }
        $field = ($field == 'userid') ? 'user_id' : $field;

        if ($field == 'longlat') {
            return null; /* to do */
            $long = isset($value['long']) ? $value['long'] : $value[0];
            $lat = isset($value['lat']) ? $value['lat'] : $value[1];
            return Mysql::query('SELECT ')->results;
        }
        if (($field == 'id' || $field == 'user_id') && !is_int($value)) {
            if (is_string($value)) {
                if (preg_match('/[^0-9]/', $post)) {
                    return null;
                }
                $value = (int)$value;
            }
            if (!is_int($value)) {
                return null;
            }
        }
        $selector = ($field == 'id' || $field == 'user_id') ? '%u' : '%s';

        $ids = Mysql::query('SELECT id FROM posts WHERE ' . $field . '=' . $selector, $value)->results;
        return array_map(
            function ($res) {
                return self::get($res->id);
            },
            $ids
        );
    }

    public static function get($id)
    {
        if (isset(self::$cache['id'][$post->id]) && is_object(self::$cache['id'][$post->id])) {
            return self::$cache['id'][$post->id];
        }

        $post = Mysql::query('SELECT * FROM posts WHERE id=%u', $id)->row;
        $post->user = User::get($post->user_id);
        self::$cache['id'][$post->id] = $post;

        unset($post); // clean teh lulz

        return self::$cache['id'][$post->id];
    }
}