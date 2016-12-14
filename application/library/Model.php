<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Model base class. All models should extend this class.
 *
 * @package    Elixir
 * @category   Models
 * @author    知名不具
 * @copyright  (c) 2016-2017 Elixir Team
 * @license
 */
abstract class Model
{

    /**
     * 表名
     * @var string
     */
    protected $_table = '';
    /**
     * 表主键
     * @var string
     */
    protected $_primary = 'id';
    /**
     * 表字段
     * @var array
     */
    protected $_fields = array();

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
     * 插入时，准备好的数据
     *
     * @var array
     */
    protected $_data = array();

    protected $db = NULL;
    protected $error_code = 0;
    protected $error_message = '';

    /**
     * 分页
     * @var null
     */
    protected $pagination = NULL;

    public function __construct()
    {
        $this->db = Database::instance();
    }

    /**
     * Create a new model instance.
     *
     *     $model = Model::factory($name);
     *
     * @param   string $name model name
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
     * @param array $where 查询条件
     * @param array $fields 字段
     * @param string $order 排序
     * @param int $limit 数量
     * @param int $offset 偏移量
     * @param bool $cache 是否缓存？
     * @return array
     */
    public function select(array $where, array $fields = NULL, string $order = '', int $limit = 0, int $offset = 0,
                           bool $cache = FALSE): array
    {
        $query = DB::select_array($fields)->from($this->_table);
        $this->where($query, $where);
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
        $data = $query->execute($this->db)->result();
        if (!$data) {
            $this->error = 'nothing.';
            return [];
        }
        $this->_after_select($data, TRUE);
        return $data;
    }

    /**
     * 分页
     * @param array $where 查询条件
     * @param array $fields 字段
     * @param string $order 排序
     * @param int $page 页码
     * @param int $size 每页数量
     * @param bool $cache 是否缓存查询结果？
     * @return array
     */
    public function paging(array $where = NULL, array $fields = NULL, string $order = '', int $page = 1, int $size = 16,
                         bool $cache = FALSE): array
    {
        static $count_items = 0;
        if ($count_items === 0) {
            $count_items = $this->count_records($where, TRUE);
        }
        $this->pagination = Pagination::factory($page, $count_items, $size);
        if ($this->pagination->count_items <= 0)
            return [];
        return $this->select($where, $fields, $order, $this->pagination->items_per_page, $this->pagination->items_offset, $cache);
    }

    /**
     * 获取总数
     *
     * @param array $where
     * @param bool $cache 是否缓存查询结果？
     * @return int
     */
    public function count_records(array $where = NULL, bool $cache = FALSE): int
    {
        $query = DB::select(DB::expr('COUNT(*) AS total'))->from($this->_table);
        $this->where($query, $where);
        $cache AND $query->cached();
        $record_count = $query->limit(1)->execute($this->db)->get('total');
        return $record_count ? intval($record_count) : 0;
    }

    /**
     * 读一列值
     * @param int $id
     * @param string $fields
     * @param bool $cache 是否缓存？
     * @return array
     */
    public function get($id, string $field, bool $cache = FALSE): string
    {
        $query = DB::select(array($field, 'alias'))->from($this->_table)->where($this->_primary, '=', $id)->limit(1);
        $cache AND $query->cached();
        $data = $query->execute($this->db)->get('alias', '');
        return $data ?: '';
    }
    /**
     * 读一列值
     * @param array $where
     * @param string $fields
     * @param bool $cache 是否缓存？
     * @return string
     */
    public function get_array($where, string $field, bool $cache = FALSE): string
    {
        $query = DB::select_array(array($field, 'alias'))->from($this->_table);
        $this->where($query, $where);
        $query->limit(1);
        $cache AND $query->cached();
        $data = $query->execute($this->db)->get('alias', '');
        return $data ?: '';
    }
    /**
     * 读一行记录
     * @param int $id
     * @param array|NULL $fields
     * @param bool $cache 是否缓存？
     * @return array
     */
    public function find(int $id, array $fields = NULL, bool $cache = FALSE): array
    {
        $query = DB::select_array($fields)->from($this->_table)->where($this->_primary, '=', $id)->limit(1);
        $cache AND $query->cached();
        $data = $query->execute($this->db)->current();
        return $data ?: array();
    }
    /**
     * 读一行记录
     * @param array $where
     * @param array|NULL $fields
     * @param bool $cache 是否缓存？
     * @return array
     */
    public function find_array(array $where, array $fields = NULL, bool $cache = FALSE): array
    {
        $query = DB::select_array($fields)->from($this->_table);
        $this->where($query, $where);
        $query->limit(1);
        $cache AND $query->cached();
        $data = $query->execute($this->db)->current();
        return $data ?: array();
    }
    /**
     * 插入一条记录
     * @param array $data
     * @return integer  新记录ID
     */
    public function insert(array $data): int
    {
        $insert_id = 0;
        $this->_create_autofill($data);
        try {
            list($insert_id, $affected_rows) = DB::insert($this->_table)->columns(array_keys($data))
                ->values(array_values($data))->execute($this->db);
            $insert_id += 0;
        } catch (Exception $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();
        }
        return $insert_id;
    }

    /**
     * 更新一条记录
     * @param array $data
     * @param int $primary_id
     * @return bool
     */
    public function update(array $data, int $primary_id): bool
    {
        $this->_readonly($data);
        $this->_update_autofill($data);
        try {
            DB::update($this->_table)->set($data)->where($this->_primary, '=', $primary_id)->execute($this->db);
            return TRUE;
        } catch (Exception $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();
        }
        return FALSE;
    }

    /**
     * 删除记录
     * @param array|int $primary_id
     * @return bool
     */
    public function delete($primary_id): bool
    {
        $op = is_array($primary_id) ? 'IN' : '=';
        try {
            DB::delete($this->_table)->where($this->_primary, $op, $primary_id)->execute($this->db);
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
     * @return boolean
     */
    public function exists(array $where = NULL): bool
    {
        $query = DB::select(DB::expr('1'))->from($this->_table);
        $this->where($query, $where);
        $ret = $query->limit(1)->execute($this->db)->get('1');
        return $ret ? TRUE : FALSE;
    }

    /**
     * 根据ID拷贝一份
     *
     * @param int $id 主键ID
     * @param array $data 覆盖字段值
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
        return isset($this->_primary) ? $this->_primary : $this->db->get_primary($this->_table);
    }
    /**
     * 数据输出前处理
     * @param array $r
     */
    abstract protected function output(array &$r);

    /**
     * select查询之后的处理
     * @param array $data
     * @param bool $multiple
     */
    protected function _after_select(array &$data, $multiple = FALSE)
    {
        if (!$data) {
            return;
        }
        if ($multiple) {
            array_walk($data, array($this, "output"));
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
                    $query->where(DB::expr('INSTR(' . $this->db->quote_column($field) . ', ?1)', ['?1' => $val]), '>', 0);
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
     * @return string
     */
    public function where_keywords(string $field, string $keywords): string
    {
        $keywords = trim($keywords);
        if ($keywords === '') return '';
        $where = ' AND INSTR(' . $this->db->quote_column($field) . ', ' . $this->db->quote($keywords) . ') > 0 ';
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
     * @return null
     */
    public function pagination()
    {
        return $this->pagination;
    }


    /**
     * 过滤只读字段
     * @param $data
     * @return void
     */
    private function _readonly(&$data)
    {
        if (empty($this->_readonly)) return;
        foreach ($this->_readonly as $field => $val) {
            if (isset($data[$field])) unset($data[$field]);
        }
    }

    /**
     * 插入时自动填充
     * @param $data
     * @return void
     */
    private function _create_autofill(&$data)
    {
        if (empty($this->_create_autofill)) return;
        foreach ($this->_create_autofill as $field => $val) {
            if (!isset($data[$field])) $data[$field] = $val;
        }
    }

    /**
     * 更新时自动填充
     * @param $data
     */
    private function _update_autofill(&$data)
    {
        if (empty($this->_update_autofill)) return;
        foreach ($this->_update_autofill as $field => $val) {
            if (!isset($data[$field])) $data[$field] = $val;
        }
    }

}
