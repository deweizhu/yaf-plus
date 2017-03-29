<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * PDO database connection.
 *
 * @package    Elixir/Database
 * @category   Drivers
 * @author    知名不具
 * @copyright  (c) 2016-2017 Elixir Team
 * @license
 */
class Database_PDO extends Database {

	// PDO uses no quoting for identifiers
	protected $_identifier = '';

	public function __construct(string $name, array $config)
	{
		parent::__construct($name, $config);

		if (isset($this->_config['identifier']))
		{
			// Allow the identifier to be overloaded per-connection
			$this->_identifier = (string) $this->_config['identifier'];
		}
	}

	public function connect()
	{
		if ($this->_connection)
			return;

		// Extract the connection parameters, adding required variabels
		extract($this->_config['connection'] + array(
			'dsn'        => '',
			'username'   => NULL,
			'password'   => NULL,
			'persistent' => FALSE,
		));

		// Clear the connection parameters for security
		unset($this->_config['connection']);

		// Force PDO to use exceptions for all errors
		$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

		if ( ! empty($persistent))
		{
			// Make the connection persistent
			$options[PDO::ATTR_PERSISTENT] = TRUE;
		}

		try
		{
			// Create a new PDO connection
			$this->_connection = new PDO($dsn, $username, $password, $options);
		}
		catch (PDOException $e)
		{
			throw new Elixir_Exception(':error',
				array(':error' => $e->getMessage()),
				$e->getCode());
		}

		if ( ! empty($this->_config['charset']))
		{
			// Set the character set
			$this->set_charset($this->_config['charset']);
		}
	}

	/**
	 * Create or redefine a SQL aggregate function.
	 *
	 * [!!] Works only with SQLite
	 *
	 * @link http://php.net/manual/function.pdo-sqlitecreateaggregate
	 *
	 * @param   string      $name       Name of the SQL function to be created or redefined
	 * @param   callback    $step       Called for each row of a result set
	 * @param   callback    $final      Called after all rows of a result set have been processed
	 * @param   integer     $arguments  Number of arguments that the SQL function takes
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
	 * @param   string      $name       Name of the SQL function to be created or redefined
	 * @param   callback    $callback   Callback which implements the SQL function
	 * @param   integer     $arguments  Number of arguments that the SQL function takes
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

	public function disconnect():bool
	{
		// Destroy the PDO object
		$this->_connection = NULL;

		return parent::disconnect();
	}

	public function set_charset(string $charset)
	{
		// Make sure the database is connected
		$this->_connection OR $this->connect();

		// This SQL-92 syntax is not supported by all drivers
		$this->_connection->exec('SET NAMES '.$this->quote($charset));
	}

	public function query(int $type, string $sql, $as_object = FALSE, array $params = [],array $ctorargs = [])
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		try
		{
		    $sth = $this->_connection->prepare($sql);
		}
		catch (Exception $e)
		{
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
	    $sth->execute($params);

		if ($type === Database::SELECT)
		{
			// Convert the result into an array, as PDOStatement::rowCount is not reliable
			if ($as_object === FALSE)
			{
				$sth->setFetchMode(PDO::FETCH_ASSOC);
			}
			elseif (is_string($as_object))
			{
				$sth->setFetchMode(PDO::FETCH_CLASS, $as_object, $ctorargs);
			}
			else
			{
				$sth->setFetchMode(PDO::FETCH_CLASS, 'stdClass');
			}

			 $result = $sth->fetchAll();

			// Return an iterator of results
			return new Database_Result_Cached($result, $sql, $as_object, $params);
		}
		elseif ($type === Database::INSERT)
		{
			// Return a list of insert id and rows created
			return array(
				$this->_connection->lastInsertId(),
				$sth->rowCount(),
			);
		}
		else
		{
			// Return the number of rows affected
			return $sth->rowCount();
		}
	}

	public function begin(string $mode = ''): bool
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->beginTransaction();
	}

	public function commit(): bool
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->commit();
	}

	public function rollback(): bool
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->rollBack();
	}

	public function list_tables(string $like = ''): string
	{
        if (is_string($like))
        {
            // Search for table names
            $result = $this->query(Database::SELECT, 'SHOW TABLES LIKE '.$this->quote($like), FALSE);
        }
        else
        {
            // Find all table names
            $result = $this->query(Database::SELECT, 'SHOW TABLES', FALSE);
        }

        $tables = array();
        foreach ($result as $row)
        {
            $tables[] = reset($row);
        }

        return $tables;
	}

	public function list_columns(string $table, string $like = NULL, bool $add_prefix = TRUE): array
	{
        // Quote the table name
        $table = ($add_prefix === TRUE) ? $this->quote_table($table) : $table;

        if (is_string($like))
        {
            // Search for column names
            $result = $this->query(Database::SELECT, 'SHOW FULL COLUMNS FROM '.$table.' LIKE '.$this->quote($like), FALSE);
        }
        else
        {
            // Find all column names
            $result = $this->query(Database::SELECT, 'SHOW FULL COLUMNS FROM '.$table, FALSE);
        }

        $count = 0;
        $columns = array();
        foreach ($result as $row)
        {
            list($type, $length) = $this->_parse_type($row['Type']);

            $column = $this->datatype($type);

            $column['column_name']      = $row['Field'];
            $column['column_default']   = $row['Default'];
            $column['data_type']        = $type;
            $column['is_nullable']      = ($row['Null'] == 'YES');
            $column['ordinal_position'] = ++$count;

            switch ($column['type'])
            {
                case 'float':
                    if (isset($length))
                    {
                        list($column['numeric_precision'], $column['numeric_scale']) = explode(',', $length);
                    }
                    break;
                case 'int':
                    if (isset($length))
                    {
                        // MySQL attribute
                        $column['display'] = $length;
                    }
                    break;
                case 'string':
                    switch ($column['data_type'])
                    {
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
            $column['comment']      = $row['Comment'];
            $column['extra']        = $row['Extra'];
            $column['key']          = $row['Key'];
            $column['privileges']   = $row['Privileges'];

            $columns[$row['Field']] = $column;
        }

        return $columns;
	}

    public function get_primary(string $table, bool $add_prefix = TRUE)
    {
        // Quote the table name
        $table = ($add_prefix === TRUE) ? $this->quote_table($table) : $table;

        $primary = array();
        $result = $this->query(Database::SELECT, 'SHOW COLUMNS FROM '.$table, FALSE);
        foreach ($result as $r)
        {
            if($r['Key'] == 'PRI') $primary[] = $r['Field'];
        }
        return count($primary) == 1 ? $primary[0] : (empty($primary) ? null : $primary);
    }

	public function escape(string $value): string
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->quote($value);
	}

} // End Database_PDO
