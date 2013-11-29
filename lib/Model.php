<?php

/**
 * @author Hosh Sadiq
 * @copyright 2012
 * @todo add methods for where and all that crap, keep it simple
 * @todo implement join and alias
 */
class Model extends Object
{
    private $_results = array();
    private $_is_new = false;
    private $_is_req = true;
    private $_fields_info = array();
    private $_id = 0;
    private $_id_field = '';
    private $_unique_keys = array();
    private $_table = ''; // contains the current table
    private $_query = null;
    private $_dirty_fields = array();
    private $_alias = null;
    private $_references = array();
    private $_where = '';
    private $_limit = null;
    private $_offset = null;
    private $_order = array(); // field => asc|desc
    private $_distinct = ''; // all, distinct or distinctrow
    private $_group = array();
    private $_select = array(); // field, field, table_alias.field as field_alias
    // etc
    public function __construct($table = null)
    {
        if ($table == null && get_class($this) != 'Model') {
            $table = substr(get_class($this), 0, -5);
        }
        $table = Inflector::tableize($table);

        if (Mysql::table_exists($table)) {
            $this->_table = $table;
            $this->_is_new = true;
            $this->getFieldsInfo();
            $this->getForeignKeys();
        } else {
            throw new ModelException(__('Model "' . $table . '" does not exist'));
        }
    }

    public function get($id = null, $id_field = null)
    {
        try {
            if ($id != null) {
                if ($this->_id_field == '' || count($this->_unique_keys) < 1) {
                    throw new ModelException(__('Tried to get Model by ID, but not Model ID field detected'));
                }

                $id = Mysql::escape($id);

                if ($id_field == null) {
                    $where = array(
                        $this->_id_field . '=' . $id
                    );
                    if (count($this->_unique_keys) > 0) {
                        foreach ($this->_unique_keys as $key) {
                            $where[] = $key . '=' . $id;
                        }
                    }
                } else {
                    $where = array(
                        $id_field . '=' . $id
                    );
                }
                $this->_query = Mysql::query(
                    'SELECT * FROM ' . $this->_table . ' WHERE ' . implode(' OR ', $where) . ' LIMIT 1;'
                );
                $this->_results = $this->_query->row;

                $this->_is_new = false;
                $this->_is_req = false;
                $this->_id = intval($this->_results->{$this->_id_field});
            } elseif ($this->isQuery()) {
                $this->_is_req = true;
                $this->_isnew = false;
                $this->exec();
            } else {
                $this->_is_new = false;
                $this->_is_req = false;
                $this->_id = 0;
            }
        } catch (PDOException $e) {
            throw new ModelException($e->getMessage());
        }
        return $this;
    }

    private function getFieldsInfo()
    {
        $list = array(
            MYSQLI_ENUM_FLAG,
            MYSQLI_SET_FLAG
        );
        $flags = array(
            'unsigned' => MYSQLI_UNSIGNED_FLAG,
            'auto' => MYSQLI_AUTO_INCREMENT_FLAG,
            'not_null' => MYSQLI_NOT_NULL_FLAG
        );
        $this->_query = Mysql::query('SELECT * FROM ' . $this->_table . ' LIMIT 0');
        $cols = $this->_query->col_info(null);
        // var_dump($this);
        foreach ($cols as $col) {
            if ($col->flags & MYSQLI_PRI_KEY_FLAG) {
                $this->_id_field = $col->name;
            } elseif ($col->flags & MYSQLI_UNIQUE_KEY_FLAG) {
                $this->_unique_keys[] = $col->name;
            } elseif ($col->flags & MYSQLI_MULTIPLE_KEY_FLAG) {
                // var_dump(Mysql::query('SHOW COLUMNS FROM `' . $this->_table .
                // '` WHERE `Field`=\''.$col->name.'\'')->row);
                // var_dump($col);
            }
            if ($col->flags & MYSQLI_ENUM_FLAG || $col->flags & MYSQLI_SET_FLAG) {
                preg_match_all(
                    '/\'([\w ]*)\'/',
                    Mysql::query(
                        'SHOW COLUMNS FROM `' . $this->_table . '` WHERE `Field`=\'' . $col->name . '\''
                    )->var_Type,
                    $values
                );
                array_unshift($values[1], '');
                $col->values = $values[1];
            }
            foreach ($flags as $flag => $check) {
                $col->$flag = $col->flags & $check ? true : false;
            }
            $this->_fields_info[$col->name] = $col;
        }
        if ($this->_id_field == '') {
            throw new ModelException(__('All Models require a primary key'));
        }
    }

    private function getForeignKeys()
    {
        $query = Mysql::query(
            'SELECT table_name, column_name, referenced_table_name, referenced_column_name
                                               FROM information_schema.KEY_COLUMN_USAGE
                                               WHERE REFERENCED_TABLE_SCHEMA =  \'' . DB::NAME . '\'
										AND REFERENCED_TABLE_NAME IS NOT NULL
										AND (table_name = \'' . $this->_table . '\'
											OR referenced_table_name = \'' . $this->_table . '\')
									ORDER BY TABLE_NAME, COLUMN_NAME'
        );
        $this->_references = array(
            'foreign' => array(),
            'local' => array()
        );
        foreach ($query->results as $result) {
            if ($result->table_name == $this->_table) {
                $this->_references['foreign'][] = array(
                    'column' => $result->column_name,
                    'for_table' => $result->referenced_table_name,
                    'for_column' => $result->referenced_column_name
                );
            } elseif ($result->referenced_table_name == $this->_table) {
                $this->_references['local'][] = array(
                    'from_table' => $result->table_name,
                    'from_column' => $result->column_name,
                    'column' => $result->referenced_column_name
                );
            }
        }
    }

    /**
     *
     * @todo take into account the field's type and all that
     */
    public function save()
    {
        if ($this->isQuery()) {
            // maybe throw error?
            throw new ModelException(__(
                'Tried to save for non-savable Model, please create new Model(\'' . $this->_table . '\');'
            ));
        }

        $update = $this->_id > 0;

        if ($update && count($this->_dirty_fields) < 1) {
            return;
        }

        $sql = $update && is_object(
            $this->_results
        ) ? 'UPDATE ' . $this->_table . ' SET %s WHERE ' . $this->_id_field . '=' . $this->_id : 'INSERT INTO ' . $this->_table . ' SET %s';

        $fields = array();
        foreach ($this->_results as $field => $value) {
            if (!in_array($field, $this->_dirty_fields)) {
                continue;
            }
            $fields[$field] = $value;
        }

        $this->_query = Mysql::query(sprintf($sql, Mysql::get_set($fields)));
        $insert_id = $this->_query->insertid;
        if (!$update) {
            $this->get($insert_id);
        }
        return $this;
    }

    public function delete()
    {
        if (!$this->isQuery() && $this->_id > 0) {
            $this->_db->query('DELETE FROM ' . $this->_table . ' WHERE ' . $this->_id_field . '=' . $this->_id);
        } else {
            // var_dump($this->_where);
            if ($this->_where == '') {
                throw new ModelException(__('Cannot delete without where conditions unless id is set.'));
            } else {
                $sql = array(
                    'DELETE FROM ' . $this->_table
                );
                $sql[] = ' WHERE ' . $this->_where;
                $this->_limit !== null ? $sql[] = 'LIMIT ' . $this->_limit : 'LIMIT 1';

                $sql = implode(' ', $sql);

                $this->_query = Mysql::query($sql);
                $this->reset();
            }
        }
        return $this->reset();
    }

    // quick dummy implementation, needs more
    public function __call($name, $args)
    {
        if (preg_match('/^([s|g]et)_([a-zA-Z_][a-zA-Z0-9_]*)$/', $name, $matches)) {
            if ($matches[1] == 'set') {
                return $this->{substr($name, 4)} = $args[0];
            }
            return $this->{substr($name, 4)};
        }
        return $this;
    }

    public function __get($name)
    {
        $name = Inflector::underscore($name);
        if (isset($this->_fields_info[$name]) && !$this->isQuery()) {
            return isset($this->_results->$field) ? $this->_results->$field : null;
        } else {
            return parent::instance()->$name;
        }
    }

    public function __set($name, $value)
    {
        $name = Inflector::underscore($name);
        if (isset($this->_fields_info[$name]) && !$this->isQuery()) {
            $this->_is_req = false;
            if (!is_object($this->_results)) {
                $this->_results = new stdClass();
            }
            $this->_dirty_fields[] = $name;
            return $this->_results->$name = $args[0];
        } else {
            return parent::instance()->$name = $value;
        }
    }

    public function __isset($name)
    {
        return isset($this->_results->$name);
    }

    public function __unset($name)
    {
        return $this->$name = $this->_fields_info[$name]->def;
    }

    public function __toString()
    {
        return $this->_query->query;
    }

    public function __clone()
    {
        // lulz cannot clone brav
    }

    public function asArray()
    {
        return object_to_array($this->_results);
    }

    public function asObject()
    {
        return $this->_results;
    }

    private function isQuery()
    {
        return $this->_is_req === true || $this->_id < 1; // id is still 0
    }

    private function reset()
    {
        $this->_results = array();
        $this->_is_new = false;
        $this->_is_req = true;
        $this->_fields_info = array();
        $this->_id = 0;
        $this->_id_field = '';
        $this->_unique_keys = array();
        $this->_dirty_fields = array();
        $this->_alias = null;

        $this->_where = '';
        $this->_limit = null;
        $this->_offset = null;
        $this->_order = array();
        $this->_distinct = '';
        $this->_group = array();
        $this->_select = array();
        return $this;
    }

    public function exec()
    {
        $select = implode(', ', $this->_select);
        $select = $select == '' ? '*' : $select;

        $distinct = ($select != '*' && $this->_distinct != '') ? $this->_distinct . ' ' : '';

        $sql = array(
            'SELECT ' . $distinct . $select . ' FROM ' . $this->_table
        );
        if ($this->_where != '') {
            $sql[] = ' WHERE ' . $this->_where;
        }

        $order = array();
        foreach ($this->_order as $field => $direction) {
            $order[] = $field . ' ' . $direction;
        }

        count($this->_group) > 0 ? $sql[] = 'GROUP BY ' . implode(', ', $this->_group) : null;
        count($order) > 0 ? $sql[] = 'ORDER BY ' . implode(', ', $order) : null;
        $limit = array();
        if ($this->_offset !== null) {
            $limit[] = $this->_offset;
        }
        if ($this->_limit !== null) {
            $limit[] = $this->_limit;
        }
        $this->_limit !== null ? $sql[] = 'LIMIT ' . $this->_limit : null;
        $this->_offset !== null ? $sql[] = 'OFFSET ' . $this->_offset : null;

        $sql = implode(' ', $sql);

        $this->_query = Mysql::query($sql);
        $this->_results = $this->_query->results;

        return $this;
    }

    // can be used for more advanced conditions only
    // $clear_where is false, $add will be added before it
    public function where($where)
    {
        if (func_num_args() > 1) {
            $values = func_get_args();
            array_shift($values);
            $where = vsprintf(
                $where,
                array_map(
                    array(
                        'Mysql',
                        'escape'
                    ),
                    $values
                )
            );
        }
        $this->_where = $where;
        return $this;
    }

    public function _and($where)
    {
        if ($where == false) {
            return $this;
        }
        if (func_num_args() > 1) {
            $values = func_get_args();
            array_shift($values);
            $where = vsprintf(
                $where,
                array_map(
                    array(
                        'Mysql',
                        'escape'
                    ),
                    $values
                )
            );
        }
        $this->_where .= ' AND ' . $where;
        return $this;
    }

    public function _or($where)
    {
        if ($where == false) {
            return $this;
        }
        if (func_num_args() > 1) {
            $values = func_get_args();
            array_shift($values);
            $where = vsprintf(
                $where,
                array_map(
                    array(
                        'Mysql',
                        'escape'
                    ),
                    $values
                )
            );
        }
        $this->_where .= ' OR ' . $where;
        return $this;
    }

    public function get_where()
    {
        return $this->_where;
    }

    public function _limit($limit)
    {
        $this->_limit = (int)$limit;
        return $this;
    }

    public function _offset($offset)
    {
        $this->_offset = (int)$offset;
        return $this;
    }

    public function setLimit($limit, $offset = null)
    {
        $this->_limit($limit);
        if ($offset != null) {
            $this->_offset($offset);
        }
        return $this;
    }

    public function addOrder($field, $order = 'asc')
    {
        $this->_order[] = $order;
        return $this;
    }

    public function distinct($dist = 'distinct')
    {
        $this->_distinct = ($dist == 'row') ? 'distinctrow' : $dist;
        return $this;
    }

    public function group()
    {
        $this->_group = array_merge($this->_group, func_get_args());
        return $this;
    }

    public function select($select)
    {
        $this->_select[] = $select;
        return $this;
    }
}
