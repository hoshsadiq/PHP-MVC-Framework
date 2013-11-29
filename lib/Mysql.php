<?php

final class Mysql
{
    /**
     * Contains the number of queries executed
     * @var int
     */
    private static $num_queries = 0;

    /**
     * Contains the last query executed
     * @see Mysql::query()
     * @var Mysql_Query
     */
    private static $last_query = null;

    /**
     * Contains an array of all the tables in the database
     * @see Mysql::get_tables()
     * @var array
     */
    private static $_tables = array();

    /**
     * Contains the connection to mysqli
     * @see Mysql::connect()
     * @var mysqli
     */
    private static $_dbh = null;


    /**
     * Cannot instantiate
     */
    public function __construct()
    {
        if (isset($this)) {
            throw new MysqlException('Class Mysql cannot be instantiated');
        }
    }

    /**
     * Connects to MySQLi, connection settings are set within Config/Db.php
     *
     * @see mysqli_connect();
     *
     * @return bool returns whether or not it was able to connect
     */
    public static function connect()
    {
        // If there is no existing database connection then try to connect
        if (self::$_dbh != null && is_a(self::$_dbh, 'mysqli')) {
            return true;
        }

        // set required defines
        Maker::def('OBJECT', 'OBJECT');
        Maker::def('ARRAY_A', 'ARRAY_A');
        Maker::def('ARRAY_N', 'ARRAY_N');

        Maker::def('ESCAPE_AUTO', 0);
        Maker::def('ESCAPE_STR', 1);
        Maker::def('ESCAPE_INT', 2);
        Maker::def('ESCAPE_FLOAT', 3);
        Maker::def('ESCAPE_BOOL', 4);

        // Must have a user and a password
        if (!self::$_dbh = new mysqli(DB::HOST, DB::USER, DB::PASS, DB::NAME)) { // Try to establish the server database handle
            exit(__('Error establishing MySQL database connection.'));
            return false;
        }

        self::$_dbh->set_charset('utf8');
        //self::get_tables();
        return true;
    }

    /**
     * Perform MySQL query and try to determin result value
     *
     * @see mysqli_query();
     * @see mysqli_free_result()
     * @see Mysql_Result
     *
     * @param string $query The MySQL query
     */
    public static function query($query)
    {
        if (func_num_args() > 1) {
            $values = func_get_args();
            array_shift($values);
            $query = vsprintf($query, array_map(array(__CLASS__, 'escape'), $values));
        }

        // This keeps the connection alive for very long running scripts
        if (self::$num_queries >= 500) {
            self::disconnect();
        }
        // If there is no existing database connection then try to connect
        self::connect();

        // Flush cached values..
        $query = new Mysql_Result(trim($query));

        // Keep track of the last query for debug..
        self::$last_query = & $query;

        // Count how many queries there have been
        self::$num_queries++;

        // If there is an error then take note of it..
        if ($query->error) {
            Maker::log($query->error, 'mysql');
            return false;
        }

        return $query;
    }

    /**
     * Close the active mySQL connection
     *
     * @see mysqli_close();
     */
    public static function disconnect()
    {
        // If there is no existing database connection then try to connect
        if (self::$_dbh != null && is_a(self::$_dbh, 'mysqli')) {
            return true;
        }
        self::$_dbh->close();
    }

    /**
     * Checks whether or not a specified table exists
     *
     * @return boolean True if tables exists, otherwise false
     */
    public static function table_exists($table)
    {
        return in_array($table, self::get_tables());
    }

    /**
     * Returns a list of tables in the current DB
     *
     * @return array An array of tables
     */
    public static function get_tables()
    {
        if (count(self::$_tables) > 0) {
            return self::$_tables;
        }

        $query = self::query('SHOW TABLES');
        self::$_tables = array_map(
            function ($item) {
                return $item->{'Tables_in_' . DB::NAME};
            },
            $query->results
        );
        return self::$_tables;
    }

    /**
     * Returns the last sql executed
     *
     * @return string The last executed query
     */
    public static function last_query()
    {
        return self::$last_query;
    }

    /**
     * Format a MySQL string correctly for safe MySQL insert
     *
     * @see Mysql::val()
     *
     * @param mixed $data Array of data or just one var. Will automatically determine type and return the right value
     * @return $data with the escaped values. If array, it will keep the same array structure
     */

    public static function escape($data, $type = ESCAPE_AUTO, $add_quotes = true)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = is_array($v) || is_object($v) ? self::escape($v, $type, $add_quotes) : self::safe(
                    $v,
                    $type,
                    $add_quotes
                );
            }
        } elseif (is_object($data)) {
            foreach ($data as $k => $v) {
                $data->$k = is_array($v) || is_object($v) ? self::escape($v, $type, $add_quotes) : self::safe(
                    $v,
                    $type,
                    $add_quotes
                );
            }
        } else {
            $data = self::safe($data, $type, $add_quotes);
        }

        return $data;
    }


    /**
     * Real escape, using mysqli_real_escape_string()
     * The following conversions will happen if type is ESCAPE_AUTO
     * If a string is converted, they will be case incensitive
     *
     * 'NULL', null becomes 'NULL'
     * true, false, 'true', 'false' becomes 1
     *
     * @see mysqli_real_escape_string()
     * @access private
     *
     * @param  string $string to escape
     * @return string escaped
     */
    public static function safe($str, $type = ESCAPE_AUTO, $add_quotes = true)
    {
        self::connect();
        if ($type == ESCAPE_AUTO) {
            if (strtolower($str) == 'null' || $str === null) {
                return 'NULL';
            } elseif (is_bool($str) || strtolower($str) == 'true' || strtolower($str) == 'false') {
                return (int)($str == true || $str == 'true');
            } elseif (is_int($str) || preg_match('/^[1-9](?:[0-9]+)?$/', $str)) {
                return ( int )$str;
            } elseif (!is_scalar($str)) {
                return serialize($str);
            } elseif (is_float($str) || is_double($str) || is_real($str)) {
                return ( float )$str;
            } else {
                $quotes = $add_quotes ? '\'' : '';
                return $quotes . self::$_dbh->real_escape_string(( string )$str) . $quotes;
            }
        } elseif ($type == ESCAPE_STR) {
            $quotes = $add_quotes ? '\'' : '';
            return $quotes . self::$_dbh->real_escape_string(( string )$str) . $quotes;
        } elseif ($type == ESCAPE_INT) {
            return ( int )$str;
        } elseif ($type == ESCAPE_FLOAT) {
            return ( float )$str;
        } elseif ($type == ESCAPE_BOOL) {
            return ( bool )$str;
        }
    }

    /**
     * Creates a SET sql string from an associative array (and escapes all values)
     * Note: this function will automatically determine the type of the variable
     *
     * @example
     * <code>
     * $db_data = array('login'=>'jv','email'=>'jv@vip.ie', 'user_id' => 1, 'created' => 'NOW()');
     * $db->query("INSERT INTO users SET ".$db->get_set($db_data));
     * </code>
     * ...OR...
     * <code>
     * $db->query("UPDATE users SET ".$db->get_set($db_data)." WHERE user_id = 1");
     * </code>
     *
     * Output:
     * <code>
     * login = 'jv', email = 'jv@vip.ie', user_id = 1, created = NOW()
     * </code>
     *
     * @see Mysql::escape()
     *
     * @param array $params An associative array with in the form of fieldname => fieldvalue
     * @return string SQL params
     */
    public static function get_set($params)
    {
        $sql = array();
        foreach ($params as $field => $val) {
            $sql[] = $field . ' = ' . (strtolower($val) == 'now()' ? 'NOW()' : self::escape($val));
        }

        return implode(', ', $sql);
    }

    public static function get_dbh()
    {
        return self::$_dbh;
    }
}
