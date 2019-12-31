<?php

/**
 * Database connection wrapper/helper.
 *
 * You may get a database instance using `Database::instance('name')` where
 * name is the [config](database/config) group.
 *
 * This class provides connection instance management via Database Drivers, as
 * well as quoting, escaping and other related functions. Querys are done using
 * [Database_Query] and [Database_Query_Builder] objects, which can be easily
 * created using the [DB] helper class.
 *
 * @package        Elixir/Database
 * @category       Base
 * @author         Not well-known man
 * @copyright  (c) 2016-2017 Elixir Team
 * @license
 */
abstract class Elixir_Database
{

    // Query types
    const SELECT = 1;
    const INSERT = 2;
    const UPDATE = 3;
    const DELETE = 4;

    protected $transactions = 0;

    /**
     * @var  string  default instance name
     */
    public static $default = 'default';

    /**
     * @var  array  Database instances
     */
    public static $instances = array();


    /**
     * 数据库驱动
     * @var string
     */
    public static $driver = '';
    const MYSQL = 'mysql';
    const MSSQL = 'sqlsrv';

    /**
     * Get a singleton Database instance. If configuration is not specified,
     * it will be loaded from the database configuration file using the same
     * group as the name.
     *
     *     // Load the default database
     *     $db = Database::instance();
     *
     *     // Create a custom configured instance
     *     $db = Database::instance('custom', $config);
     *
     * @param   string $name instance name
     * @param   array $config configuration parameters
     *
     * @return  Database
     */
    public static function instance(string $name = 'default', array $config = NULL): Database
    {
        if (!isset(Database::$instances[$name])) {
            if ($config === NULL) {
                // Load the configuration for this database
                $config = Yaf\Application::app()->getConfig()->get('database.' . $name);
            }

            $_config = array('charset' => $config->charset);
            $_config['connection'] = array(
                'dsn' => $config->dsn,
                'username' => $config->username,
                'password' => $config->password,
                'persistent' => $config->persistent,
            );
            $_config['table_prefix'] = $config->table_prefix;

            //sqlsrv:server=tcp:127.0.0.1,1433; Database=test;
            if (strncmp($config->dsn, 'sqlsrv', 6) === 0) {
                self::$driver = self::MSSQL;
                preg_match('#(?:.*)Database=([^;]+)#', $config->dsn, $matches);
                $_config['dbname'] = $matches[1];
                // Create the database connection instance
                Database::$instances[$name] = new Database_MSSQL($name, $_config);
            } else {
                //mysql:host=127.0.0.1;dbname=test
                self::$driver = self::MYSQL;
                preg_match('#(?:.*)dbname=([^;]+)#', $config->dsn, $matches);
                $_config['dbname'] = $matches[1];
                // Create the database connection instance
                Database::$instances[$name] = new Database_MySQL($name, $_config);
            }
        }

        return Database::$instances[$name];
    }


    /**
     * @var  string  the last query executed
     */
    public $last_query;

    // Character that is used to quote identifiers
    protected $_identifier = '`';
    protected $_identifier_end = '`';

    // Instance name
    protected $_instance;

    // Raw server connection
    protected $_connection;

    // Configuration array
    protected $_config;

    /**
     * Stores the database configuration locally and name the instance.
     *
     * [!!] This method cannot be accessed directly, you must use [Database::instance].
     *
     * @return  void
     */
    public function __construct(string $name, array $config)
    {
        // Set the instance name
        $this->_instance = $name;

        // Store the config locally
        $this->_config = $config;

        if (empty($this->_config['table_prefix'])) {
            $this->_config['table_prefix'] = '';
        }
    }

    /**
     * Disconnect from the database when the object is destroyed.
     *
     *     // Destroy the database instance
     *     unset(Database::instances[(string) $db], $db);
     *
     * [!!] Calling `unset($db)` is not enough to destroy the database, as it
     * will still be stored in `Database::$instances`.
     *
     * @return  void
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Returns the database instance name.
     *
     *     echo (string) $db;
     *
     * @return  string
     */
    public function __toString(): string
    {
        return $this->_instance;
    }

    /**
     * Connect to the database. This is called automatically when the first
     * query is executed.
     *
     *     $db->connect();
     *
     * @throws  Elixir_Exception
     * @return  void
     */
    abstract public function connect();

    /**
     * Disconnect from the database. This is called automatically by [Database::__destruct].
     * Clears the database instance from [Database::$instances].
     *
     *     $db->disconnect();
     *
     * @return  boolean
     */
    public function disconnect(): bool
    {
        unset(Database::$instances[$this->_instance]);

        return TRUE;
    }

    /**
     * Set the connection character set. This is called automatically by [Database::connect].
     *
     *     $db->set_charset('utf8');
     *
     * @throws  Elixir_Exception
     *
     * @param   string $charset character set name
     *
     * @return  void
     */
    abstract public function set_charset(string $charset);

    /**
     * Perform an SQL query of the given type.
     *
     *     // Make a SELECT query and use objects for results
     *     $db->query(Database::SELECT, 'SELECT * FROM groups', TRUE);
     *
     *     // Make a SELECT query and use "Model_User" for the results
     *     $db->query(Database::SELECT, 'SELECT * FROM users LIMIT 1', 'Model_User');
     *
     * @param   integer $type Database::SELECT, Database::INSERT, etc
     * @param   string $sql SQL query
     * @param   bool $as_object result object class string, TRUE for stdClass, FALSE for assoc array
     * @param   array $params object construct parameters for result class
     *
     * @return  object   Database_Result for SELECT queries
     * @return  array    list (insert id, row count) for INSERT queries
     * @return  integer  number of affected rows for all other queries
     */
    abstract public function query(int $type, string $sql, $as_object = FALSE, array $params = [], array $ctorargs = []);

    /**
     *  执行select SQL
     * @param string $sql
     * @param array $params
     * @param bool $as_object
     * @param array $ctorargs
     * @return object
     */
    public function select(string $sql, array $params = [], $as_object = FALSE, array $ctorargs = [])
    {
        return $this->query(Database::SELECT, $sql, $as_object, $params, $ctorargs);
    }

    /**
     * 执行insert SQL
     * @param string $sql
     * @param array $params
     * @return object
     */
    public function insert(string $sql, array $params = [])
    {
        return $this->query(Database::INSERT, $sql, FALSE, $params, []);
    }

    /**
     *  执行 update SQL
     * @param string $sql
     * @param array $params
     * @return object
     */
    public function update(string $sql, array $params = [])
    {
        return $this->query(Database::UPDATE, $sql, FALSE, $params, []);
    }

    /**
     *  执行 delete SQL
     * @param string $sql
     * @param array $params
     * @return object
     */
    public function delete(string $sql, array $params = [])
    {
        return $this->query(Database::UPDATE, $sql, FALSE, $params, []);
    }


    /**
     * Start a SQL transaction
     *
     *     // Start the transactions
     *     $db->begin();
     *
     *     try {
     *          DB::insert('users')->values($user1)...
     *          DB::insert('users')->values($user2)...
     *          // Insert successful commit the changes
     *          $db->commit();
     *     }
     *     catch (Elixir_Exception $e)
     *     {
     *          // Insert failed. Rolling back changes...
     *          $db->rollback();
     *      }
     *
     * @param string $mode transaction mode
     *
     * @return  boolean
     */
    abstract public function begin(string $mode = ''): bool;

    /**
     * Commit the current transaction
     *
     *     // Commit the database changes
     *     $db->commit();
     *
     * @return  boolean
     */
    abstract public function commit(): bool;

    /**
     * Abort the current transaction
     *
     *     // Undo the changes
     *     $db->rollback();
     *
     * @return  boolean
     */
    abstract public function rollback(): bool;

    /**
     * Count the number of records in a table.
     *
     *     // Get the total number of records in the "users" table
     *     $count = $db->count_records('users');
     *
     * @param   mixed $table table name string or array(table, alias)
     * @param   string $where query string
     *
     * @return  integer
     */
    public function count_records($table, string $where = ''): int
    {
        // Quote the table name
        $table = $this->quote_table($table);
        $total_row_count = $this->query(Database::SELECT, 'SELECT COUNT(*) AS total_row_count FROM ' . $table . $where, FALSE)
            ->get('total_row_count');
        return $total_row_count ? intval($total_row_count) : 0;
    }

    /**
     * Returns a normalized array describing the SQL data type
     *
     *     $db->datatype('char');
     *
     * @param   string $type SQL data type
     *
     * @return  array
     */
    public function datatype(string $type): array
    {
        static $types = array
        (
            // SQL-92
            'bit' => array('type' => 'string', 'exact' => TRUE),
            'bit varying' => array('type' => 'string'),
            'char' => array('type' => 'string', 'exact' => TRUE),
            'char varying' => array('type' => 'string'),
            'character' => array('type' => 'string', 'exact' => TRUE),
            'character varying' => array('type' => 'string'),
            'date' => array('type' => 'string'),
            'dec' => array('type' => 'float', 'exact' => TRUE),
            'decimal' => array('type' => 'float', 'exact' => TRUE),
            'double precision' => array('type' => 'float'),
            'float' => array('type' => 'float'),
            'int' => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
            'integer' => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
            'interval' => array('type' => 'string'),
            'national char' => array('type' => 'string', 'exact' => TRUE),
            'national char varying' => array('type' => 'string'),
            'national character' => array('type' => 'string', 'exact' => TRUE),
            'national character varying' => array('type' => 'string'),
            'nchar' => array('type' => 'string', 'exact' => TRUE),
            'nchar varying' => array('type' => 'string'),
            'numeric' => array('type' => 'float', 'exact' => TRUE),
            'real' => array('type' => 'float'),
            'smallint' => array('type' => 'int', 'min' => '-32768', 'max' => '32767'),
            'time' => array('type' => 'string'),
            'time with time zone' => array('type' => 'string'),
            'timestamp' => array('type' => 'string'),
            'timestamp with time zone' => array('type' => 'string'),
            'varchar' => array('type' => 'string'),

            // SQL:1999
            'binary large object' => array('type' => 'string', 'binary' => TRUE),
            'blob' => array('type' => 'string', 'binary' => TRUE),
            'boolean' => array('type' => 'bool'),
            'char large object' => array('type' => 'string'),
            'character large object' => array('type' => 'string'),
            'clob' => array('type' => 'string'),
            'national character large object' => array('type' => 'string'),
            'nchar large object' => array('type' => 'string'),
            'nclob' => array('type' => 'string'),
            'time without time zone' => array('type' => 'string'),
            'timestamp without time zone' => array('type' => 'string'),

            // SQL:2003
            'bigint' => array('type' => 'int', 'min' => '-9223372036854775808', 'max' => '9223372036854775807'),

            // SQL:2008
            'binary' => array('type' => 'string', 'binary' => TRUE, 'exact' => TRUE),
            'binary varying' => array('type' => 'string', 'binary' => TRUE),
            'varbinary' => array('type' => 'string', 'binary' => TRUE),
        );

        if (isset($types[$type]))
            return $types[$type];

        return array();
    }

    /**
     * List all of the tables in the database. Optionally, a LIKE string can
     * be used to search for specific tables.
     *
     *     // Get all tables in the current database
     *     $tables = $db->list_tables();
     *
     *     // Get all user-related tables
     *     $tables = $db->list_tables('user%');
     *
     * @param   string $like table to search for
     *
     * @return  array
     */
    abstract public function list_tables(string $like = ''): string;

    /**
     * Lists all of the columns in a table. Optionally, a LIKE string can be
     * used to search for specific fields.
     *
     *     // Get all columns from the "users" table
     *     $columns = $db->list_columns('users');
     *
     *     // Get all name-related columns
     *     $columns = $db->list_columns('users', '%name%');
     *
     *     // Get the columns from a table that doesn't use the table prefix
     *     $columns = $db->list_columns('users', NULL, FALSE);
     *
     * @param   string $table table to get columns from
     * @param   string $like column to search for
     * @param   boolean $add_prefix whether to add the table prefix automatically or not
     *
     * @return  array
     */
    abstract public function list_columns(string $table, string $like = NULL, bool $add_prefix = TRUE): array;

    /**
     * get table primary
     *
     * @param string $table table to get columns from
     * @param bool $add_prefix whether to add the table prefix automatically or not
     *
     * @return mixed
     */
    abstract public function get_primary(string $table, bool $add_prefix = TRUE);

    /**
     * Extracts the text between parentheses, if any.
     *
     *     // Returns: array('CHAR', '6')
     *     list($type, $length) = $db->_parse_type('CHAR(6)');
     *
     * @param   string $type
     *
     * @return  array   list containing the type and length, if any
     */
    protected function _parse_type($type): array
    {
        if (($open = strpos($type, '(')) === FALSE) {
            // No length specified
            return array($type, NULL);
        }

        // Closing parenthesis
        $close = strrpos($type, ')', $open);

        // Length without parentheses
        $length = substr($type, $open + 1, $close - 1 - $open);

        // Type without the length
        $type = substr($type, 0, $open) . substr($type, $close + 1);

        return array($type, $length);
    }

    /**
     * Return the table prefix defined in the current configuration.
     *
     *     $prefix = $db->table_prefix();
     *
     * @return  string
     */
    public function table_prefix(): string
    {
        return $this->_config['table_prefix'];
    }

    /**
     * 返回当前配置的数据库名称
     *
     *     $db_name = $db->db_name();
     *
     * @return  string
     */
    public function db_name(): string
    {
        return $this->_config['dbname'];
    }

    /**
     * Quote a value for an SQL query.
     *
     *     $db->quote(NULL);   // 'NULL'
     *     $db->quote(10);     // 10
     *     $db->quote('fred'); // 'fred'
     *
     * Objects passed to this function will be converted to strings.
     * [Database_Expression] objects will be compiled.
     * [Database_Query] objects will be compiled and converted to a sub-query.
     * All other objects will be converted using the `__toString` method.
     *
     * @param   mixed $value any value to quote
     *
     * @return  string
     * @uses    Database::escape
     */
    public function quote($value): string
    {
        if ($value === NULL) {
            return 'NULL';
        } elseif ($value === TRUE) {
            return "'1'";
        } elseif ($value === FALSE) {
            return "'0'";
        } elseif (is_object($value)) {
            if ($value instanceof Database_Query) {
                // Create a sub-query
                return '(' . $value->compile($this) . ')';
            } elseif ($value instanceof Database_Expression) {
                // Compile the expression
                return $value->compile($this);
            } else {
                // Convert the object to a string
                return $this->quote((string)$value);
            }
        } elseif (is_array($value)) {
            return '(' . implode(', ', array_map(array($this, __FUNCTION__), $value)) . ')';
        }
        elseif (is_numeric($value) AND strlen($value) > 10){
            //手机号、订单号等数字
            return $this->escape($value);
        }
        elseif (is_numeric($value) AND is_int($value + 0)) {
            return (int)$value;
        } elseif (is_numeric($value) AND is_float($value + 0)) {
            // Convert to non-locale aware float to prevent possible commas
            return sprintf('%F', $value);
        }

        return $this->escape($value);
    }

    /**
     * Quote a database column name and add the table prefix if needed.
     *
     *     $column = $db->quote_column($column);
     *
     * You can also use SQL methods within identifiers.
     *
     *     $column = $db->quote_column(DB::expr('COUNT(`column`)'));
     *
     * Objects passed to this function will be converted to strings.
     * [Database_Expression] objects will be compiled.
     * [Database_Query] objects will be compiled and converted to a sub-query.
     * All other objects will be converted using the `__toString` method.
     *
     * @param   mixed $column column name or array(column, alias)
     *
     * @return  string
     * @uses    Database::quote_identifier
     * @uses    Database::table_prefix
     */
    abstract public function quote_column($column): string;

    /**
     * Quote a database table name and adds the table prefix if needed.
     *
     *     $table = $db->quote_table('table', 'alias');
     *     $table = $db->quote_table(array('table', 'alias'));
     *     $table = $db->quote_table('table');
     *
     * Objects passed to this function will be converted to strings.
     * [Database_Expression] objects will be compiled.
     * [Database_Query] objects will be compiled and converted to a sub-query.
     * All other objects will be converted using the `__toString` method.
     *
     * @param   mixed $table table name or array(table, alias)
     * @param   string|null $alias table name alias
     *
     * @return  string
     * @uses    Database::quote_identifier
     * @uses    Database::table_prefix
     */
    abstract public function quote_table($table): string;

    /**
     * Quote a database identifier
     *
     * Objects passed to this function will be converted to strings.
     * [Database_Expression] objects will be compiled.
     * [Database_Query] objects will be compiled and converted to a sub-query.
     * All other objects will be converted using the `__toString` method.
     *
     * @param   mixed $value any identifier
     *
     * @return  string
     */
    abstract public function quote_identifier($value): string;

    /**
     * Sanitize a string by escaping characters that could cause an SQL
     * injection attack.
     *
     *     $value = $db->escape('any string');
     *
     * @param   string $value value to quote
     *
     * @return  string
     */
    abstract public function escape(string $value): string;


    /**
     * 创建像这样的查询: "IN('a','b')";
     *
     * @access   public
     *
     * @param    array $item_list 列表数组或字符串
     * @param    string $field_name 字段名称
     *
     * @return   string
     */
    public function create_in(array $item_list, string $field_name = ''): string
    {
        if (!$item_list) {
            return $field_name . " IN ('') ";
        } else {
            $item_list = array_filter(array_unique($item_list), function ($v) {
                return ($v !== '');
            });
            $item_list = array_map(array($this, 'quote'), $item_list);
            $str = implode(',', $item_list);
            return $field_name . ' IN (' . $str . ') ';
        }
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
    abstract public function paging($table, $fields = '*', string $where = '', string $order = '', int $page = 1, int $size =
    16): array;


    /**
     * 是否为MySQL驱动？
     * @return bool  true|false
     */
    public static function isMySQL(): bool
    {
        return self::$driver === self::MYSQL;
    }

    /**
     * 是否为MS Sql Server驱动？
     * @return bool  true|false
     */
    public static function isMSSQL(): bool
    {
        return self::$driver === self::MSSQL;
    }

    /**
     * 获取PDO对象
     * @return mixed
     */
    public function getConnection()
    {
        $this->_connection or $this->connect();
        return $this->_connection;
    }

} // End Database_Connection
