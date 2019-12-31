<?php

/**
 * Model base class. All models should extend this class.
 *
 * @package        Elixir
 * @category       Models
 * @author         Not well-known man
 * @copyright  (c) 2016-2017 Elixir Team
 * @license
 */
abstract class Model implements ArrayAccess, JsonSerializable
{

    /**
     * 表名
     *
     * @var string
     */
    protected $_table = '';
    /**
     * 表主键
     *
     * @var string
     */
    protected $_primary = 'id';

    /**
     * 主键值自增
     * @var bool
     */
    protected $_auto_increment = TRUE;
    /**
     * 表字段
     *
     * @var array
     */
    protected $_fields = array();

    /**
     * 可批量赋值参数，当$_fields 为空是，取该值
     *
     * @var array
     */
    protected $guarded = ['*'];

    /**
     * 是否需要验证 $_fields,默认需要
     *
     * @var bool
     */
    protected static $unguarded = FALSE;

    /**
     * 查询结果集中隐藏的字段
     *
     * @var array
     */
    protected $_hidden = array();

    /**
     * 只读字段
     *
     * @var array
     */
    protected $_readonly = array();

    /**
     * 自动填充字段定义
     *
     * @var array
     */
    protected $_create_autofill = array();

    /**
     * 自动更新字段定义
     *
     * @var array
     */
    protected $_update_autofill = array();

    /**
     * 软删除
     *
     * @var string $softDelete
     */
    protected $_softDelete = FALSE;

    /**
     * 软删除字段
     *
     * @var string
     */
    protected $_softDeleteField = 'deleted_at';

    protected $_tablePrefix = NULL;

    /**
     * 插入时，准备好的数据
     *
     * @var array
     */
    protected $_data = array();

    /**
     * @var Database
     */
    protected $db = NULL;
    protected $error_code = 0;
    protected $error_message = '';

    protected static $_count_items = 0;

    /**
     * 事件
     * @var string
     */
    public $_event;

    /**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $visible = [];

    /**
     * 模型的属性值
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The model attribute's original state.
     *
     * @var array
     */
    protected $original = [];

    /**
     * 分页
     *
     * @var null
     */
    protected $pagination = NULL;

    /**
     * @var self
     */
    protected static $_instance = NULL;

    public function __construct(array $attributes = [])
    {
        $this->setTablePrefix($this->_tablePrefix);

        $this->syncOriginal();

        $this->fill($attributes);
    }

    /**
     * 单实例
     *
     * @return static
     */
    public static function instance()
    {
        if (static::$_instance === NULL || !(static::$_instance instanceof static)) {
            static::$_instance = new static;
        }
        return static::$_instance;
    }

    /**
     * Create a new model instance.
     *
     *     $model = Model::factory($name);
     *
     * @param   string $name model name
     *
     * @return  Model
     */
    public static function factory($name): Model
    {
        // Add the model prefix
        $class = $name . 'Model';

        return new $class;
    }


    /**
     * 查询多条记录
     *
     * @param array $where 查询条件
     * @param array $columns 字段
     * @param string $order 排序
     * @param int $limit 数量
     * @param int $offset 偏移量
     * @param bool $cache 是否缓存？
     *
     * @return array
     */
    public function select(array $where, array $columns = NULL, string $order = '', int $limit = 0, int $offset = 0,
                           bool $cache = FALSE): array
    {
        $query = DB::select_array($columns)->from($this->_table);
        $this->where($query, $where);
        if ($this->_softDelete) {
            $query->where($this->_softDeleteField, '=', 0);
        }
        $limit > 0 and $query->limit($limit)->offset($offset);
        $cache AND $query->cached();
        //排序
        if ($order !== '') {
            if (strpos($order, ',') === FALSE && strpos($order, ' ') != FALSE) {
                list($column, $direction) = explode(' ', $order);
                $query->order_by($column, $direction);
            } elseif (strpos($order, ',') !== FALSE) {
                foreach (explode(',', $order) as $v) {
                    if (strpos($v, ' ') === FALSE)
                        continue;
                    list($column, $direction) = explode(' ', $v);
                    $query->order_by($column, $direction);
                }
            }
        }
        $data = $query->execute(Database::instance())->result();
        if (!$data) {
            $this->error_message = 'nothing.';
            return [];
        }
        $this->_after_select($data, TRUE);
        return $data;
    }

    /**
     * 分页
     *
     * @param array $where 查询条件
     * @param array $columns 字段
     * @param string $order 排序
     * @param int $page 页码
     * @param int $size 每页数量
     * @param bool $cache 是否缓存查询结果？
     *
     * @return array
     */
    public function paging(array $where = NULL, array $columns = NULL, string $order = '', int $page = 1, int $size = 16,
                           bool $cache = FALSE): array
    {
        if (self::$_count_items === 0) {
            self::$_count_items = $this->count_records($where, $cache);
        }
        $this->pagination = Pagination::factory($page, self::$_count_items, $size);
        if ($this->pagination->count_items <= 0)
            return ['data' => array(), 'pager' => $this->pagination];
        $data = $this->select($where, $columns, $order, $this->pagination->items_per_page,
            $this->pagination->items_offset, $cache);

        return ['data' => $data, 'pager' => $this->pagination];
    }

    /**
     * 查询所有记录
     *
     * @param array $where 查询条件
     * @param array $columns 字段
     * @param bool $cache 是否缓存？
     *
     * @return array
     */
    public function fetchAll(array $where = [], array $columns = [], bool $cache = TRUE): array
    {
        $query = DB::select_array($columns)->from($this->_table);
        $this->where($query, $where);
        $cache AND $query->cached();
        $result = $query->execute(Database::instance())->result();
        if (!$result) {
            $this->error_message = 'nothing.';
            return [];
        }
        return $result;
    }

    /**
     * 获取总数
     *
     * @param array $where
     * @param bool $cache 是否缓存查询结果？
     *
     * @return int
     */
    public function count_records(array $where = NULL, bool $cache = FALSE): int
    {
        $query = DB::select(DB::expr('COUNT(*) AS total'))->from($this->_table);
        $this->where($query, $where);
        $cache AND $query->cached();
        $record_count = $query->limit(1)->execute(Database::instance())->get('total');
        return $record_count ? intval($record_count) : 0;
    }

    /**
     * 读一列值
     *
     * @param int $id
     * @param string $columns
     * @param bool $cache 是否缓存？
     *
     * @return string
     */
    public function get($id, string $field, bool $cache = FALSE): string
    {
        $query = DB::select(array($field, 'alias_fiedld'))->from($this->_table)->where($this->_primary, '=', $id)->limit(1);
        $cache AND $query->cached();
        $data = $query->execute(Database::instance())->get('alias_fiedld', '');
        return $data ?: '';
    }

    /**
     * 读一列值
     *
     * @param array $where
     * @param string $columns
     * @param bool $cache 是否缓存？
     *
     * @return string
     */
    public function get_by(array $where, string $field, bool $cache = FALSE): string
    {
        $query = DB::select(array($field, 'alias_fiedld'))->from($this->_table);
        $this->where($query, $where);
        $query->limit(1);
        $cache AND $query->cached();
        $data = $query->execute($this->db)->get('alias_fiedld', '');
        return $data ?: '';
    }

    /**
     * 读一行记录
     *
     * @param int $id
     * @param array|NULL $columns
     * @param bool $cache 是否缓存？
     *
     * @return array
     */
    public function find(int $id, array $columns = NULL, bool $cache = FALSE): array
    {
        $query = DB::select_array($columns)->from($this->_table)->where($this->_primary, '=', $id)->limit(1);
        $cache AND $query->cached();
        $data = $query->execute($this->db)->current();
        return $data ?: array();
    }

    /**
     * 读一行记录
     *
     * @param array $where
     * @param array|NULL $columns
     * @param bool $cache 是否缓存？
     *
     * @return array
     */
    public function find_by(array $where, array $columns = NULL, bool $cache = FALSE): array
    {
        $query = DB::select_array($columns)->from($this->_table);
        $this->where($query, $where);
        $query->limit(1);
        $cache AND $query->cached();
        $data = $query->execute($this->db)->current();
        return $data ?: array();
    }

    /**
     * 获取model
     *
     * @param int $id
     * @return mixed
     */
    public function find_model(int $id)
    {
        $key = crc32($this->_table . $id);

//        if ($data = Cache::instance()->get($key)) {
//            return $data;
//        } else {
            $columns = $this->_filter_hidden_fields();
            $sql = 'select ' . $columns . ' from ' . $this->_table . ' where ' . $this->_primary . '=?';
            $data = Database::instance()->query(Database::SELECT, $sql, get_class($this), [$id])->current();
            Cache::instance()->set($key, $data);

            return $data;
//        }
    }

    /**
     * 查询，返回Model类型
     *
     * @param array $options
     * @param string $order
     * @param int $limit
     * @param int $offset
     */
    public function select_model(array $options = [], string $order = NULL, int $limit = 0, int $offset = 0)
    {
        $columns = $this->_filter_hidden_fields();

        $sql = 'select ' . $columns . ' from ' . $this->_table;

        $params = array();

        if (!empty($options)) {
            $where = '';
            foreach ($options as $key => $val) {
                if ($val === NULL) {
                    $where .= ' ' . $key . ' IS NULL AND';
                } else {
                    $where .= ' ' . $key . '=:' . $key . ' AND';
                }

                $params[':' . $key] = $val;
            }
            $sql .= ' WHERE ' . substr($where, 0, strlen($where) - 3);
        }

        if (!empty($order))
            $sql .= ' ORDER BY ' . $order;

        if ($limit > 0)
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        return Database::instance()->query(Database::SELECT, $sql, get_class($this), $params)->result();
    }

    /**
     * 插入一条记录
     *
     * @param array $data
     *
     * @return integer  新记录ID
     */
    public function insert(array $data): int
    {
        $insert_id = 0;
        if (!empty($this->_fields)) {
            if (!empty($this->_hidden)) {
                $columns = array_unique(array_merge($this->_fields, $this->_hidden));
            } else {
                $columns = $this->_fields;
            }
            //新增时过滤自增主键
            if ($this->_auto_increment && $this->_primary && isset($data[$this->_primary]))
                unset($data[$this->_primary]);
            $data = Arr::filter_array($data, $columns);
        }
        $this->_create_autofill($data);
        try {
            list($insert_id, $affected_rows) = DB::insert($this->_table)->columns(array_keys($data))
                ->values(array_values($data))->execute(Database::instance());
            $insert_id += 0;
            if ($insert_id === 0 && $affected_rows > 0 && isset($data[$this->_primary]))
                $insert_id = $data[$this->_primary] + 0;
        } catch (Exception $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();
        }
        return $insert_id;
    }

    /**
     * 更新一条记录
     *
     * @param array $data
     * @param int $primary_id
     *
     * @return bool
     */
    public function update(array $data, int $primary_id): bool
    {
        $this->_readonly($data);
        $this->_update_autofill($data);
        try {
            DB::update($this->_table)->set($data)->where($this->_primary, '=', $primary_id)->execute(Database::instance());
            return TRUE;
        } catch (Exception $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();
        }
        return FALSE;
    }

    /**
     * 删除记录
     *
     * @param array|int $primary_id
     *
     * @return bool
     */
    public function delete($primary_id): bool
    {
        $op = is_array($primary_id) ? 'IN' : '=';
        try {
            if ($this->_softDelete) {
                DB::update($this->_table)->set([$this->_softDeleteField => time()])->where($this->_primary, $op, $primary_id)->execute(Database::instance());
            } else {
                DB::delete($this->_table)->where($this->_primary, $op, $primary_id)->execute(Database::instance());
            }
            if (is_array($primary_id)) {
                foreach ($primary_id as $p) {
                    $key = crc32($this->_table . $p);
                    Cache::instance()->delete($key);
                }
            } else {
                $key = crc32($this->_table . $primary_id);
                Cache::instance()->delete($key);
            }
            return TRUE;
        } catch (Exception $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();
        }
        return FALSE;
    }

    /**
     * 删除
     * @return bool
     */
    public function destory(): bool
    {
        try {
            if ($this->_softDelete) {
                DB::update($this->_table)->set([$this->_softDeleteField => time()])->where($this->_primary, '=', $this->{$this->_primary})->execute(Database::instance());
            } else {
                DB::delete($this->_table)->where($this->_primary, '=', $this->{$this->_primary})->execute(Database::instance());
            }
            return TRUE;
        } catch (Exception $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();
        }
        return FALSE;
    }

    /**
     * 恢复软删除数据
     *
     * @param int|array $primary_id
     * @return bool
     */
    public function restore($primary_id): bool
    {
        if ($this->_softDelete) {
            $op = is_array($primary_id) ? $primary_id : [$primary_id];

            $strIds = explode(',', $primary_id);

            $sql = 'UPDATE ' . Database::instance()->table_prefix() . $this->_table . ' set ' . $this->_softDeleteField . '=? WHERE id in(?)';

            try {
                Database::instance()->query(DATABASE::UPDATE, $sql, FALSE, [0, $strIds]);
                return TRUE;
            } catch (Exception $e) {
                $this->error_code = $e->getCode();
                $this->error_message = $e->getMessage();
            }

            return FALSE;
        }
        return FALSE;
    }

    /**
     * 按条件删除
     *
     * @param array $where
     *
     * @return bool
     */
    public function delete_by(array $where): bool
    {
        try {
            if ($this->softDelete) {
                $query = DB::update($this->_table)->set([$this->softDeleteField => $_SERVER['REQUEST_TIME']]);
            } else {
                $query = DB::delete($this->_table);
            }
            $this->where($query, $where);
            $query->execute($this->db);
            return TRUE;
        } catch (Exception $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();
        }
        return FALSE;
    }

    /**
     * 是否存在符合条件的记录
     *
     * @param array $where
     * @param mixed $value
     *
     * @return boolean
     */
    public function exists(array $where = NULL): bool
    {
        $query = DB::select(DB::expr('1 AS ok'))->from($this->_table);
        $this->where($query, $where);
        $ret = $query->limit(1)->execute()->get('ok');
        return $ret ? TRUE : FALSE;
    }

    /**
     * 是否存在符合条件的ID？
     *
     * @param int $primary_id 主键ID
     *
     * @return bool
     */
    public function exists_id(int $primary_id): bool
    {
        $ret = DB::select(DB::expr('1 AS ok'))->from($this->_table)
            ->where($this->_primary, '=', $primary_id)->limit(1)
            ->execute()->get('ok');
        return $ret ? TRUE : FALSE;
    }

    /**
     * 根据ID拷贝一份
     *
     * @param int $id 主键ID
     * @param array $data 覆盖字段值
     *
     * @return int
     */
    public function copy_by_id(int $id, array $data = NULL): int
    {
        $r = $this->find($id);
        if (!$r) return 0;
        unset($r[$this->_primary]);
        if ($data) $r = array_merge($r, $data);
        return $this->insert($r);
    }

    /**
     * 更新一个字段值
     *
     * @param string $field
     * @param mixed $value
     * @param int $primary_id
     */
    public function set_field(string $field, string $value, int $primary_id): bool
    {
        return $this->update(array($field => $value), $primary_id);
    }

    /**
     * 递增一个int字段值
     *
     * @param string $field
     * @param int $primary_id
     * @param int $step
     *
     * @return boolean
     */
    public function set_inc(string $field, int $primary_id, int $step = 1): bool
    {
        $query = DB::update($this->_table)->set(array($field => DB::expr(sprintf('`%s` + %d', $field, $step))))
            ->where($this->_primary, '=', $primary_id)->limit(1);
        return $query->execute($this->db) ? TRUE : FALSE;
    }

    /**
     * 递减一个int字段值
     *
     * @param string $field
     * @param int $primary_id
     * @param int $step
     *
     * @return boolean
     */
    public function set_dec(string $field, int $primary_id, int $step = 1): bool
    {
        $query = DB::update($this->_table)->set(array($field => DB::expr(sprintf('`%s` - %d', $field, $step))))
            ->where($this->_primary, '=', $primary_id)->limit(1);
        return $query->execute($this->db) ? TRUE : FALSE;
    }

    /**
     * 获取主健定义
     *
     * @return string
     */
    public function getPrimary()
    {
        return isset($this->_primary) ? $this->_primary : Database::instance()->get_primary($this->_table);
    }

    /**
     * 获取表名
     *
     * @return string
     */
    public function getTable(string $alias = '')
    {
        return $alias ? Database::instance()->quote_table(array(Database::instance()->db_name() . '.' . $this->_table, $alias)) : Database::instance()->quote_table($this->_table);
    }

    /**
     * 数据输出前处理
     *
     * @param array $r
     */
    abstract protected function output(array &$r);

    /**
     * select查询之后的处理
     *
     * @param array $data
     * @param bool $multiple
     */
    protected function _after_select(array &$data, $multiple = FALSE)
    {
        if (!$data) {
            return;
        }
        if ($multiple) {
            array_walk($data, array($this, 'output'));
        } else {
            $this->output($data);
        }
        return;
    }

    /**
     * 通用AND查询条件
     * 用法：
     * $this->where($query, ['id' => 100, ['title','like', '测试'], ['time', '>=', time()]]);
     *
     * @param Database_Query_Builder_Where $where
     * @param array $option
     */
    public function where(Database_Query_Builder_Where &$query, array $option)
    {
        if (!$option)
            return;
        foreach ($option as $k => $v) {
            if (is_array($v)) {
                list($field, $op, $val) = $v;
                if (strtolower($op) === 'like'):
                    if (Database::isMSSQL()):
                        $query->where($field, $op, '%' . $val . '%');
                    else:
                        $query->where(DB::expr('INSTR(' . Database::instance()->quote_column($field) . ', ?1)', ['?1' => $val]), '>', 0);
                    endif;
                else:
                    $query->where($field, $op, $val);
                endif;
            } else {
                $query->where($k, '=', $v);
            }
        }
        return;
    }

    /**
     * SQL: 通用AND查询条件
     * 用法：
     *  $where = ' WHERE 1 ';
     *  $where .= $this->where_and(['id' => 100, ['title','like', '测试'], ['time', '>=', time()]]);
     *
     * @param array $option
     *
     * @return string
     */
    public function where_and(array $option): string
    {
        if (!$option)
            return '';
        $where = '';
        foreach ($option as $k => $v) {
            if (is_array($v)) {
                list($field, $op, $val) = $v;
                if (strtolower($op) === 'like'):
                    $where .= ' AND INSTR(' . $this->db->quote_column($field) . ', ' . $this->db->quote($val) . ') > 0 ';
                else:
                    $where .= ' AND ' . $this->db->quote_column($field) . $op . $this->db->quote($val);
                endif;
            } else {
                $where .= ' AND ' . $this->db->quote_column($k) . '=' . $this->db->quote($v);
            }
        }
        return $where;
    }

    /**
     * SQL: 获得某个时间之后条件语句
     *
     * @param string $field
     * @param string $mintime 时间戳
     *
     * @return string
     */
    public function where_mintime(string $field, string $mintime): string
    {
        if (!$mintime) return '';
        $mintime = trim($mintime);
        if (!is_numeric($mintime)) {
            if (strlen($mintime) == 10) $mintime .= ' 00:00:00';
            $mintime = strtotime($mintime);
        }
        $where = $this->db->quote_column($field) . '>=' . $this->db->quote($mintime);
        return $where;
    }

    /**
     * SQL: 获得某个时间之前条件语句
     *
     * @param string $field
     * @param string $maxtime 时间戳
     *
     * @return string
     */
    public function where_maxtime(string $field, string $maxtime): string
    {
        if (!$maxtime) return '';
        $maxtime = trim($maxtime);
        if (!is_numeric($maxtime)) {
            if (strlen($maxtime) == 10) $maxtime .= ' 23:59:59';
            $maxtime = strtotime($maxtime);
        }
        $where = $this->db->quote_column($field) . '<=' . $this->db->quote($maxtime);
        return $where;
    }

    /**
     * SQL: 获得键字查询语句
     *
     * @param string $field
     * @param string $keywords
     *
     * @return string
     */
    public function where_keywords(string $field, string $keywords): string
    {
        $keywords = trim($keywords);
        if ($keywords === '') return '';
        if (Database::isMSSQL())
            $where = $this->db->quote_column($field) . ' LIKE ' . $this->db->quote('%' . $keywords . '%') . '';
        else
            $where = ' INSTR(' . $this->db->quote_column($field) . ', ' . $this->db->quote($keywords) . ') > 0 ';
        return $where;
    }


    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->error_code;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->error_message;
    }

    /**
     *
     * @return Pagination
     */
    public function pagination()
    {
        return $this->pagination;
    }


    /**
     * 过滤只读字段
     *
     * @param $data
     *
     * @return void
     */
    private function _readonly(&$data)
    {
        if (isset($data[$this->_primary])) unset($data[$this->_primary]);
        if (empty($this->_readonly)) return;
        foreach ($this->_readonly as $field => $val) {
            if (isset($data[$field])) unset($data[$field]);
        }
    }

    /**
     * 插入时自动填充
     *
     * @param $data
     *
     * @return void
     */
    private function _create_autofill(&$data)
    {
        if (empty($this->_create_autofill)) return;
        foreach ($this->_create_autofill as $field => $val) {
            if (!isset($data[$val])) $data[$field] = $val;
        }
    }

    /**
     * 更新时自动填充
     *
     * @param $data
     */
    private function _update_autofill(&$data)
    {
        if (empty($this->_update_autofill)) return;
        foreach ($this->_update_autofill as $field => $val) {
            if (!isset($data[$field])) $data[$field] = $val;
        }
    }

    /**
     * 隐藏不需要的展示的字段
     *
     * @param $data
     */
    protected function _filter_hidden_fields()
    {
        if (!empty($this->_fields)) {
            $this->_fields = array_unique(array_merge($this->_fields, [$this->_primary]));

            if (!empty($this->_hidden)) {
                Arr::filter_array($this->_fields, $this->_hidden);
            }

            $columns = $this->_fields;
        } elseif ($this->guarded) {
            $columns = $this->guarded;
        }

        $columns = $this->columnize($columns);

        return $columns;
    }

    /**
     * 新增
     *
     * @param array $values
     * @return Model
     */
    protected function performInsert(array $values = [])
    {
        $table = $this->_table;

        $this->fireEvent('creating');

        foreach ($this->attributes as $key => $val) {
            if (in_array($key, array_merge($this->_fields, [$this->_primary]))) {
                $values[$key] = $val;
            }
        }

        if (!empty($this->_create_autofill)) {
            $values = array_merge($values, $this->_create_autofill);
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }

        $bindings = [];

        foreach ($values as $record) {
            foreach ($record as $value) {
                $bindings[] = $value;
            }
        }
        $parameters = [];

        foreach ($values as $record) {
            $parameters[] = '(' . $this->parameterize($record) . ')';
        }

        $parameters = implode(', ', $parameters);

        $columns = $this->columnize(array_keys(reset($values)));

        $insert_sql = "INSERT INTO $table ($columns) values $parameters";

        list($insert_id, $row) = Database::instance()->query(Database::INSERT, $insert_sql, get_class($this), array_values($bindings));

        $this->setAttribute($this->_primary, $insert_id);

        $this->fireEvent('created');

        return $this;
    }

    /**
     * 更新
     *
     * @param array $values
     * @return Model
     */
    protected function performUpdate(array $values = [])
    {
        $this->fireEvent('updating');

        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            if (!empty($this->_update_autofill)) {
                $dirty = array_merge($dirty, $this->_update_autofill);
            }

            $table = $this->_table;

            $columns = [];

            foreach ($dirty as $key => $value) {
                $columns[] = $this->wrap($key) . ' = ' . $this->parameter($value);
            }

            $columns = implode(', ', $columns);

            $primary_id = $this->{$this->_primary};

            $update_sql = trim("update {$table} set $columns WHERE {$this->_primary}={$primary_id}");

            Database::instance()->query(Database::UPDATE, $update_sql, get_class($this), array_values($dirty));

            $this->fireEvent('updated');
            $key = crc32($this->_table . $primary_id);
            if (Cache::instance()->get($key)) {
                Cache::instance()->delete($key);
            }
        }

        return $this;
    }

    /**
     * 获取更新数据
     *
     * @return []
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (in_array($key, array_merge($this->_fields, [$this->_primary]))) {
                if (!array_key_exists($key, $this->original)) {
                    $dirty[$key] = $value;
                } elseif ($value !== $this->original[$key] && !$this->originalIsNumericallyEquivalent($key)) {
                    $dirty[$key] = $value;
                }
            }
        }

        return $dirty;
    }

    /**
     * 保证更新字段，是需要更新的
     *
     * @param  string $key
     * @return bool
     */
    protected function originalIsNumericallyEquivalent($key)
    {
        $current = $this->attributes[$key];

        $original = $this->original[$key];

        return is_numeric($current) && is_numeric($original) && strcmp((string)$current, (string)$original) === 0;
    }

    /**
     * Run the save method on the model
     *
     * @param array $option
     *
     * @return Object
     */
    public function save(array $options = [])
    {
        $this->fireEvent('saving');

        if (!empty($options))
            $this->fill($options);

        $primary_id = $this->_primary;


        if (empty($this->$primary_id)) {
            if (!empty($options[$this->_primary])) {
                $this->setAttribute($this->_primary, $options[$this->_primary]);
            }
            $saved = $this->performInsert();
        } else {
            $saved = $this->performUpdate();
        }


        $this->fireEvent('saved');

        return $saved;
    }

    /**
     * 填充数据
     *
     * @param array $attributes
     * @throws Exception
     * @return Model
     */
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            $key = $this->removeTableFromKey($key);

            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            } elseif ($totallyGuarded) {
                throw new Exception();
            }
        }

        return $this;
    }

    /**
     * Determine if the model is totally guarded.
     *
     * @return bool
     */
    public function totallyGuarded()
    {
        return count($this->_fields) == 0 && $this->guarded == ['*'];
    }

    protected function fillableFromArray(array $attributes)
    {
        if (count($this->_fields) > 0) {
            return array_intersect_key($attributes, array_flip($this->_fields));
        }

        return $attributes;
    }

    protected function removeTableFromKey($key)
    {
        if (!Text::contains($key, '.')) {
            return $key;
        }

        return end(explode('.', $key));
    }

    /**
     * 是否为可填充数据
     *
     * @param string $key
     * @return boolean
     */
    public function isFillable(string $key): bool
    {
        if (static::$unguarded) {
            return TRUE;
        }

        if (in_array($key, $this->_fields)) {
            return TRUE;
        }

        if ($this->isGuarded($key)) {
            return FALSE;
        }

        return empty($this->_fields) && !Text::startsWith($key, '_');
    }

    /**
     * 给定的值属否可以批量赋值
     *
     * @param  string $key
     * @return bool
     */
    public function isGuarded(string $key): bool
    {
        return in_array($key, $this->guarded) || $this->guarded == ['*'];
    }

    /**
     * 设置属性
     *
     * @param string $key
     * @param string|array $value
     * @return Model
     */
    public function setAttribute(string $key, $value)
    {
        if ($this->hasSetMutator($key)) {
            $method = 'set' . Text::studly($key) . 'Attribute';

            return $this->{$method}($value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }


    /**
     * Determine if a set mutator exists for an attribute.
     *
     * @param  string $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return method_exists($this, 'set' . Text::studly($key) . 'Attribute');
    }

    /**
     * 是否需要观察者
     *
     * @return boolean
     */
    public function hasNotify()
    {
        return method_exists($this, 'notify');
    }

    /**
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the array of model attributes. No checking is done.
     *
     * @param  array $attributes
     * @param  bool $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = FALSE)
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    /**
     * Sync the original attributes with the current.
     *
     * @return $this
     */
    public function syncOriginal()
    {
        $this->original = $this->attributes;

        return $this;
    }

    /**
     * 获取model的属性值
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * 将属性值赋给model
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * 将model转换成array
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = $this->attributesToArray();

        return $attributes;
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = $this->getArrayableAttributes();

        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable attributes.
     *
     * @return array
     */
    protected function getArrayableAttributes()
    {
        return $this->getArrayableItems($this->attributes);
    }

    /**
     * Get an attribute array of all arrayable values.
     *
     * @param  array $values
     * @return array
     */
    protected function getArrayableItems(array $values)
    {
        if (count($this->getVisible()) > 0) {
            return array_intersect_key($values, array_flip($this->getVisible()));
        }

        return array_diff_key($values, array_flip($this->getHidden()));
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->getAttributeValue($key);
        }
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        $value = $this->getAttributeFromArray($key);

        return $value;
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
    }

    /**
     * Get the visible attributes for the model.
     *
     * @return array
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set the visible attributes for the model.
     *
     * @param  array $visible
     * @return $this
     */
    public function setVisible(array $visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Add visible attributes for the model.
     *
     * @param  array|string|null $attributes
     * @return void
     */
    public function addVisible($attributes = NULL)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->visible = array_merge($this->visible, $attributes);
    }

    public function addHidden($attributes = NULL)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->_hidden = array_merge($this->_hidden, $attributes);
    }

    /**
     * Make the given, typically hidden, attributes visible.
     *
     * @param  array|string $attributes
     * @return $this
     */
    public function withHidden($attributes)
    {
        $this->_hidden = array_diff($this->_hidden, (array)$attributes);

        return $this;
    }

    /**
     * 获取模型隐藏的属性
     *
     * @return array
     */
    public function getHidden()
    {
        return $this->_hidden;
    }

    /**
     * 格式化 需要插入的字段
     *
     * @param array $columns
     * @return string
     */
    public function columnize(array $columns)
    {
        return implode(', ', array_map([$this, 'wrap'], $columns));
    }

    /**
     * 格式化需要插入的参数
     *
     * @param array $values
     * @return string
     */
    public function parameterize(array $values)
    {
        return implode(', ', array_map([$this, 'parameter'], $values));
    }

    /**
     * 参数形式
     *
     * @param string $value
     * @param bool $anonymous
     * @return string
     */
    public function parameter($value, bool $anonymous = FALSE)
    {
        return $anonymous ? $this->getValue($value) : '?';
    }

    public function wrap($value, $prefixAlias = FALSE)
    {
        if (strpos(strtolower($value), ' as ') !== FALSE) {
            $segments = explode(' ', $value);

            if ($prefixAlias) {
                $segments[2] = $this->_tablePrefix . $segments[2];
            }

            return $this->wrap($segments[0]) . ' as ' . $this->wrapValue($segments[2]);
        }

        $wrapped = [];

        $segments = explode('.', $value);

        foreach ($segments as $key => $segment) {
            if ($key == 0 && count($segments) > 1) {
                $wrapped[] = $this->wrapTable($segment);
            } else {
                $wrapped[] = $this->wrapValue($segment);
            }
        }

        return implode('.', $wrapped);
    }

    public function wrapTable($table)
    {
        return $this->wrap(Database::instance()->table_prefix() . $table, TRUE);
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        return '`' . str_replace('"', '""', $value) . '`';
    }

    /**
     * Get the value of a raw expression.
     *
     * @param string $value
     * @return string
     */
    public function getValue($value)
    {
        return ':' . $value;
    }

    /**
     * @return the $_tablePrefix
     */
    public function getTablePrefix(): string
    {
        return $this->_tablePrefix;
    }

    /**
     * @param string $_tablePrefix
     */
    public function setTablePrefix(string $tablePrefix = NULL)
    {
        if (empty($tablePrefix)) {
            $tablePrefix = 'xcms_';
        }

        $this->_tablePrefix = $tablePrefix;
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string $key
     * @return bool
     */
    public function __isset($key)
    {
        if (isset($this->attributes[$key])) {
            return TRUE;
        }

        if (method_exists($this, $key) && $this->$key) {
            return TRUE;
        }

        return !is_null($this->getAttributeValue($key));
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * 消息通知
     *
     * @param string $event
     */
    public function fireEvent(string $event, bool $isFire = FALSE)
    {
        if (!$isFire) {
            if ($this->hasNotify()) {
                $this->_event = $event;
                $this->notify();
            }
        }
    }


    /**
     * 单表插入时，指定主键值时，需兼容MSSQL特殊处理
     * @param string $sql
     * @return object
     */
    public function identityInsert(string $sql, string $table)
    {
        $db = Database::instance();
        if (Database::isMSSQL())
            $db->update('SET IDENTITY_INSERT ' . $this->_tablePrefix . $table . '  ON');
        //
        $ret = $db->query(Database::INSERT, $sql, FALSE);
        //
        if (Database::isMSSQL())
            $db->update('SET IDENTITY_INSERT ' . $this->_tablePrefix . $table . '  OFF');
        return $ret;
    }
}
