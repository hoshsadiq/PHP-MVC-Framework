<?php

final class Mysql_Result
{
    private $num_rows = 0;
    private $rows_affected = 0;
    private $insert_id = null;

    private $col_info = null;
    private $results = null;

    // contains mysqli result
    private $result = null;

    private $error = null;
    private $_dbh = null;

    /**
     *  Constructor - allow the user to perform a quick connect at the
     *  same time as initialising the Mysql class
     */

    public function __construct($query)
    {
        $_dbh = Mysql::get_dbh();
        $this->query = $query;

        // Perform the query via std mysql_query function..
        $this->result = $_dbh->query($query);

        // If there is an error then take note of it..
        if ($_dbh->error) {
            $this->error = $_dbh->error;
            return;
        }

        if (preg_match('/^(insert|replace)\s+/i', $query)) {
            $this->insert_id = $_dbh->insert_id;
        } // Query was an delete, update
        elseif (preg_match('/^\s*(delete|update) /i', $query)) {
            // Return number fo rows affected
            $this->rows_affected = $_dbh->affected_rows;
        } elseif (preg_match('/^\s*(show|select) /i', $query)) { // Query was a select

            // save column info and results
            while ($this->col_info[] = $this->result->fetch_field()) {
                ;
            }
            while ($this->results[] = $this->result->fetch_object()) {
                ;
            }

            // remove the last entry as that will be false
            array_pop($this->col_info);
            array_pop($this->results);

            $this->num_rows = $this->result->num_rows;

            $this->result->free();
        }
    }

    public function get_error()
    {
        if ($this->error != null) {
            return $this->error;
        }
        return null;
    }

    /**
     *  Get one variable from the DB - see docs for more detail
     */
    // no support for filtering yet
    public function _var($x = 0, $y = 0)
    {
        // Extract var out of cached results based x,y vals
        if ($this->results[$y]) {
            $values = array_values(get_object_vars($this->results[$y]));
        }

        // If there is a value return it else return null
        return (isset($values[$x]) && $values[$x] !== '') ? $values[$x] : null;
    }

    /**
     *  Get one row from the DB - see docs for more detail
     */
    // no support for filtering yet
    public function row($y = 0, $output = OBJECT)
    {
        if ($output != OBJECT && $output != ARRAY_A && $output != ARRAY_N) {
            throw new MysqlException(__(
                'Mysql_Result::row( int $y, OBJECT|ARRAY_A|ARRAY_N $output ) -- Expected OBJECT, ARRAY_A, ARRAY_N for $output, %s given',
                gettype($output)
            ));
        }

        // output the right type
        if ($this->results[$y]) {
            if ($output == OBJECT) {
                return $this->results[$y];
            } elseif ($output == ARRAY_A) {
                return get_object_vars($this->results[$y]);
            } elseif ($output == ARRAY_N) {
                return array_values(get_object_vars($this->results[$y]));
            }
        }
        return null;
    }

    /**
     *  Function to get 1 column from the cached result set based in X index
     *  see docs for usage and info
     */
    public function col($x = 0)
    {
        // Extract the column values
        $new_array = array();
        for ($i = 0; $i < count($this->results); $i++) {
            $new_array[$i] = self::_var($x, $i);
        }

        return $new_array;
    }

    /**
     * Return the the query as a result set - see docs for more details
     *
     * @param string $query Column to get
     * @param int $col_offset The offset of the column, if not set, all
     */
    public function results($output = OBJECT)
    {
        if ($output != OBJECT && $output != ARRAY_A && $output != ARRAY_N) {
            throw new MysqlException(__(
                'Mysql_Result::results( OBJECT|ARRAY_A|ARRAY_N $output ) -- Expected OBJECT, ARRAY_A, ARRAY_N for $output, %s given',
                gettype($output)
            ));
        }

        // Send back array of OBJECTs. Each row is an OBJECT
        if ($output == OBJECT) {
            return $this->results;
        } elseif ($output == ARRAY_A || $output == ARRAY_N) {
            if ($this->results) {
                $i = 0;
                foreach ($this->results as $row) {
                    $new_array[$i] = get_object_vars($row);
                    if ($output == ARRAY_N) {
                        $new_array[$i] = array_values($new_array[$i]);
                    }
                    $i++;
                }

                return $new_array;
            } else {
                return null;
            }
        }
    }

    /**
     * Function to get column meta data info pertaining to the last query
     * see docs for more info and usage
     *
     * @param string $info_type Column to get
     * @param int $col_offset The offset of the column, if not set, all
     */
    public function col_info($info_type = 'name', $col_offset = -1)
    {
        if ($this->col_info) {
            if ($info_type == null) {
                return $this->col_info;
            }
            if ($col_offset == -1) {
                $new_array = array();
                foreach ($this->col_info as $col) {
                    //var_dump($col);
                    $new_array[] = $col->{$info_type};
                }
                return $new_array;
            } else {
                return $this->col_info[$col_offset]->{$info_type};
            }
        }
    }

    /**
     * Returns the number of rows of a query for select queries
     *
     * @see mysqli_num_rows()
     *
     * @return int The number of rows
     */
    public function rows()
    {
        return $this->num_rows;
    }

    /**
     * Returns the number of rows affect from a query
     *
     * @see mysqli_rows_affected()
     *
     * @return int The number of rows affected
     */
    public function affected()
    {
        return $this->rows_affected;
    }

    /**
     * Returns the inserted ID for insert/replace query.
     * @see mysqli_insert_id()
     * @return int|string the insert ID
     */
    public function insertid()
    {
        return $this->insert_id;
    }

    public function get_query()
    {
        return $this->query;
    }

    public function __get($var)
    {
        if (preg_match('/^var(?:([0-9]+)?(?:_([0-9]+))?)?$/i', $var, $matches)) {
            $x = isset($matches[1]) && $matches[1] != '' ? (int)$matches[1] : 0;
            $y = isset($matches[2]) && $matches[2] != '' ? (int)$matches[2] : 0;
            return $this->_var($x, $y);
        } elseif (preg_match(
            '/^var_(' . implode(
                '|',
                array_map(
                    function ($item) {
                        return preg_replace('/[^a-z0-9]/i', '', $item->name);
                    },
                    (array)$this->col_info
                )
            ) . ')?$/i',
            $var,
            $matches
        )
        ) {
            return $this->row->{$matches[1]};
            /*	$x = isset($matches[1]) && $matches[1] != '' ? (int)$matches[1] : 0;
                $y = isset($matches[2]) && $matches[2] != '' ? (int)$matches[2] : 0;
                return $this->_var($x, $y);*/
        } elseif (preg_match('/^row([0-9]+)?$/i', $var, $matches)) {
            $x = isset($matches[1]) && $matches[1] != '' ? (int)$matches[1] : 0;
            return $this->row($x);
        } elseif (preg_match('/^col([0-9]+)?$/i', $var, $matches)) {
            $x = isset($matches[1]) && $matches[1] != '' ? (int)$matches[1] : 0;
            return $this->col($x);
        }

        switch ($var) {
            case 'insertid':
            case 'insert_id':
                return $this->insertid();
            case 'affected':
            case 'rows_affected':
            case 'affected_rows':
                return $this->affected();
            case 'num':
            case 'num_rows':
            case 'rows':
            case 'count':
                return $this->rows();
            case 'results':
                return $this->results();
            case 'row':
            case 'result':
                return $this->row();
            case 'sql':
            case 'query':
                return $this->get_query();
            default:
                return null;
        }
    }
}
