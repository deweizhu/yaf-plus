<?php

/**
 * MSSQL database connection.
 *
 * @package    Elixir/Database
 * @category   Drivers
 * @author    Not well-known man
 * @copyright  (c) 2016-2017 Elixir Team
 * @license
 */
class Database_MSSQL extends Database
{

    // PDO uses no quoting for identifiers
    // Character that is used to quote identifiers
    protected $_identifier = '[';
    protected $_identifier_end = ']';

    /**
     * @var PDO
     */
    protected $_connection = NULL;

    public function __construct(string $name, array $config)
    {
        parent::__construct($name, $config);
    }

    public function connect()
    {
        if ($this->_connection)
            return;

        // Extract the connection parameters, adding required variabels
        extract($this->_config['connection'] + array(
                'dsn' => '',
                'username' => NULL,
                'password' => NULL,
                'persistent' => FALSE,
            ));

        // Clear the connection parameters for security
        unset($this->_config['connection']);

        // Force PDO to use exceptions for all errors
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

        if (!empty($persistent)) {
            // Make the connection persistent
            $options[PDO::ATTR_PERSISTENT] = TRUE;
        }

        try {
            // Create a new PDO connection
            $this->_connection = new Database_PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new Elixir_Exception(':error',
                array(':error' => $e->getMessage()),
                $e->getCode());
        }

    }

    /**
     * Create or redefine a SQL aggregate function.
     *
     * [!!] Works only with SQLite
     *
     * @link http://php.net/manual/function.pdo-sqlitecreateaggregate
     *
     * @param   string $name Name of the SQL function to be created or redefined
     * @param   callback $step Called for each row of a result set
     * @param   callback $final Called after all rows of a result set have been processed
     * @param   integer $arguments Number of arguments that the SQL function takes
     *
     * @return  boolean
     */
    public function create_aggregate($name, $step, $final, $arguments = -1)
    {
        $this->_connection or $this->connect();

        return $this->_connection->sqliteCreateAggregate(
            $name, $step, $final, $arguments
        );
    }

    /**
     * Create or redefine a SQL function.
     *
     * [!!] Works only with SQLite
     *
     * @link http://php.net/manual/function.pdo-sqlitecreatefunction
     *
     * @param   string $name Name of the SQL function to be created or redefined
     * @param   callback $callback Callback which implements the SQL function
     * @param   integer $arguments Number of arguments that the SQL function takes
     *
     * @return  boolean
     */
    public function create_function($name, $callback, $arguments = -1)
    {
        $this->_connection or $this->connect();

        return $this->_connection->sqliteCreateFunction(
            $name, $callback, $arguments
        );
    }

    public function disconnect(): bool
    {
        // Destroy the PDO object
        $this->_connection = NULL;

        return parent::disconnect();
    }


    public function set_charset(string $charset)
    {
        // TODO: Implement set_charset() method.
    }


    public function query(int $type, string $sql, $as_object = FALSE, array $params = [], array $ctorargs = [])
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        //强转`xxx`为[xxx]
        $sql = preg_replace('#`([^`]+)`#', '[$1]', $sql);

        try {
            $sth = $this->_connection->prepare($sql);
        } catch (Exception $e) {
            // Convert the exception in a database exception
            throw new Elixir_Exception(':error [ :query ]',
                array(
                    ':error' => $e->getMessage(),
                    ':query' => $sql
                ),
                $e->getCode());
        }

        // Set the last query
        $this->last_query = $sql;

//		$log = Log::instance();
//		$log->add(Log::NOTICE, ':sql with param :with',[':sql'=>$sql,':with'=>json_encode($params)]);

        $sth->execute($params);

        if ($type === Database::SELECT) {
            // Convert the result into an array, as PDOStatement::rowCount is not reliable
            if ($as_object === FALSE) {
                $sth->setFetchMode(PDO::FETCH_ASSOC);
            } elseif (is_string($as_object)) {
                $sth->setFetchMode(PDO::FETCH_CLASS, $as_object, $ctorargs);
            } else {
                $sth->setFetchMode(PDO::FETCH_CLASS, 'stdClass');
            }

            $result = $sth->fetchAll();

            // Return an iterator of results
            return new Database_Result_Cached($result, $sql, $as_object, $params);
        } elseif ($type === Database::INSERT) {
            // Return a list of insert id and rows created
            return array(
                $this->_connection->lastInsertId(),
                $sth->rowCount(),
            );
        } else {
            // Return the number of rows affected
            return $sth->rowCount();
        }
    }

    public function quote_column($column): string
    {
        // 给列名加``转义符
        if (is_array($column)) {
            list($column, $alias) = $column;
            if (strpos($alias, $this->_identifier) !== FALSE)
                $alias = str_replace($this->_identifier, '', $alias);
            if (strpos($alias, $this->_identifier_end) !== FALSE)
                $alias = str_replace($this->_identifier_end, '', $alias);
        }
        if ($column instanceof Database_Query) {
            // Create a sub-query
            $column = '(' . $column->compile($this) . ')';
        } elseif ($column instanceof Database_Expression) {
            // Compile the expression
            $column = $column->compile($this);
        } else {
            // Convert to a string
            $column = (string)$column;
            if (strpos($column, $this->_identifier) !== FALSE)
                $column = str_replace($this->_identifier, '', $column);
            if (strpos($column, $this->_identifier_end) !== FALSE)
                $column = str_replace($this->_identifier_end, '', $column);

            if ($column === '*') {
                return $column;
            } elseif (strpos($column, '.') !== FALSE) {
                $parts = explode('.', $column);
                if ($prefix = $this->table_prefix()) {
                    // Get the offset of the table name, 2nd-to-last part
                    $offset = count($parts) - 2;

                    // Add the table prefix to the table name
                    $parts[$offset] = $prefix . $parts[$offset];
                }

                foreach ($parts as & $part) {
                    if ($part !== '*') {
                        // Quote each of the parts
                        $part = $this->_identifier . $part . $this->_identifier_end;
                    }
                }
                $column = implode('.', $parts);
            } else {
                $column = $this->_identifier . $column . $this->_identifier_end;
            }
        }

        if (isset($alias)) {
            $column .= ' AS ' . $this->_identifier . $alias . $this->_identifier_end;
        }
        return $column;
    }

    public function quote_table($table): string
    {
        //支持 table as alias 写法
        if (is_string($table)) {
            $table = strtolower($table);
            if (strpos($table, ' as ') !== FALSE) {
                $table = explode(' as ', $table);
                $table = array_map('trim', $table);
            }
        }

        //支持两个string参数'table', 'alias'
        if (func_num_args() === 2) {
            list($table, $alias) = func_get_args();
        } elseif (is_array($table)) {
            list($table, $alias) = $table;
        }

        if ($table instanceof Database_Query) {
            // Create a sub-query
            $table = '(' . $table->compile($this) . ')';
        } elseif ($table instanceof Database_Expression) {
            // Compile the expression
            $table = $table->compile($this);
        } else {
            // Convert to a string
            $table = (string)$table;
            if (strpos($table, '.') !== FALSE) {
                $parts = explode('.', $table);

                if ($prefix = $this->table_prefix()) {
                    // Get the offset of the table name, last part
                    $offset = count($parts) - 1;

                    // Add the table prefix to the table name
                    $parts[$offset] = $prefix . $parts[$offset];
                }

                foreach ($parts as & $part) {
                    // Quote each of the parts
                    $part = $this->_identifier . $part . $this->_identifier_end;
                }

                $table = implode('.', $parts);
            } else {
                // Add the table prefix
                $table = $this->_identifier . $this->table_prefix() . $table . $this->_identifier_end;
            }
        }

        if (isset($alias)) {
            $table .= ' AS ' . $this->_identifier . $alias . $this->_identifier_end;
        }

        return $table;
    }

    public function quote_identifier($value): string
    {
        // TODO: Implement quote_identifier() method.
        return $value;
    }


    public function list_tables(string $like = ''): string
    {
        if (is_string($like)) {
            // Search for table names
            $result = $this->query(Database::SELECT, 'SHOW TABLES LIKE ' . $this->quote($like), FALSE);
        } else {
            // Find all table names
            $result = $this->query(Database::SELECT, 'SHOW TABLES', FALSE);
        }

        $tables = array();
        foreach ($result as $row) {
            $tables[] = reset($row);
        }

        return $tables;
    }

    public function list_columns(string $table, string $like = NULL, bool $add_prefix = TRUE): array
    {
        // Quote the table name
        $table = ($add_prefix === TRUE) ? $this->quote_table($table) : $table;

        if (is_string($like)) {
            // Search for column names
            $result = $this->query(Database::SELECT, 'SHOW FULL COLUMNS FROM ' . $table . ' LIKE ' . $this->quote($like), FALSE);
        } else {
            // Find all column names
            $result = $this->query(Database::SELECT, 'SHOW FULL COLUMNS FROM ' . $table, FALSE);
        }

        $count = 0;
        $columns = array();
        foreach ($result as $row) {
            list($type, $length) = $this->_parse_type($row['Type']);

            $column = $this->datatype($type);

            $column['column_name'] = $row['Field'];
            $column['column_default'] = $row['Default'];
            $column['data_type'] = $type;
            $column['is_nullable'] = ($row['Null'] == 'YES');
            $column['ordinal_position'] = ++$count;

            switch ($column['type']) {
                case 'float':
                    if (isset($length)) {
                        list($column['numeric_precision'], $column['numeric_scale']) = explode(',', $length);
                    }
                    break;
                case 'int':
                    if (isset($length)) {
                        // MySQL attribute
                        $column['display'] = $length;
                    }
                    break;
                case 'string':
                    switch ($column['data_type']) {
                        case 'binary':
                        case 'varbinary':
                            $column['character_maximum_length'] = $length;
                            break;
                        case 'char':
                        case 'varchar':
                            $column['character_maximum_length'] = $length;
                            break;
                        case 'text':
                        case 'tinytext':
                        case 'mediumtext':
                        case 'longtext':
                            $column['collation_name'] = $row['Collation'];
                            break;
                        case 'enum':
                        case 'set':
                            $column['collation_name'] = $row['Collation'];
                            $column['options'] = explode('\',\'', substr($length, 1, -1));
                            break;
                    }
                    break;
            }

            // MySQL attributes
            $column['comment'] = $row['Comment'];
            $column['extra'] = $row['Extra'];
            $column['key'] = $row['Key'];
            $column['privileges'] = $row['Privileges'];

            $columns[$row['Field']] = $column;
        }

        return $columns;
    }

    public function get_primary(string $table, bool $add_prefix = TRUE)
    {
        // Quote the table name
        $table = ($add_prefix === TRUE) ? $this->quote_table($table) : $table;

        $primary = array();
        $result = $this->query(Database::SELECT, 'SHOW COLUMNS FROM ' . $table, FALSE);
        foreach ($result as $r) {
            if ($r['Key'] == 'PRI') $primary[] = $r['Field'];
        }
        return count($primary) == 1 ? $primary[0] : (empty($primary) ? NULL : $primary);
    }

    public function escape(string $value): string
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        return $this->_connection->quote($value);
    }

    public function begin(string $mode = ''): bool
    {
        $this->_connection OR $this->connect();
        return $this->_connection->beginTransaction();
    }

    public function commit(): bool
    {
        $this->_connection OR $this->connect();
        return $this->_connection->commit();
    }

    public function rollback(): bool
    {
        $this->_connection OR $this->connect();
        return $this->_connection->rollBack();
    }

    /**
     * 分页
     *
     * @param mixed $table 表名，字符串或数组
     * @param array $fields 字段
     * @param string $where 查询条件
     * @param string $order 排序
     * @param int $page 页码
     * @param int $size 每页数量
     *
     * @return array
     */
    public function paging($table, $fields = '*', string $where = '', string $order = '', int $page = 1, int $size = 16): array
    {
        // Quote the table name
        $table = $this->quote_table($table);
        $offset = $page > 1 ? ($page - 1) * $size : 0;
        $query = sprintf('SELECT %s FROM %s %s', $fields, $table, $where);
        $order = $order ? ' ORDER BY ' . $order : '';
        $sql = $this->_limitOffset($query, $order, $size, $offset);
        return $this->query(Database::SELECT, $sql)->result();
    }


    /**
     *  生成TOP分页SQL
     * @param string $query
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return string
     */
    private function _limitOffset(string $query, string $order, int $limit, int $offset): string
    {
        //分页+排序
        if ($limit > 0 && $offset > 0 && $order) {
            $sql = <<<EOT
SELECT TOP
	{$limit} o.* 
FROM
	( SELECT row_number () OVER ( {$order} ) AS rownumber,* FROM ( {$query} ) AS ot
	 ) AS o 
WHERE
	rownumber > {$offset}
EOT;
            return $sql;
        }
        //TOP n
        if ($limit > 0 && $pos = strpos($query, 'SELECT') !== FALSE) {
            $query = 'SELECT TOP ' . $limit . ' ' . substr($query, $pos + 6);
        }
        //排序
        if ($order)
            $query .= $order;
        return $query;
    }

} // End Database_PDO
