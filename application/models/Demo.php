<?php
declare(strict_types = 1);

/**
 *  测试文件
 *
 * @author: ZDW
 */
class DemoModel extends Model implements SplSubject
{

    protected $_table = 'users';
    protected $_primary = 'userid';
    //TODO::临时字段，请随意修改
    protected $_fields = array(
        'user_id',
        'group_id',
        'name',
        'fullname',
        'gender',
        'email',
        'mobile',
        'birthday',
        'birthday_type',
        'register_ip',
        'point',
        'disabled',
        'created',
        'updated',
    );
    protected $_create_autofill = array('created' => TIMENOW, 'updated' => 0);
    protected $_update_autofill = array('updated' => TIMENOW);
    protected $_readonly = array('user_id', 'created');
    /**
     * 事件通知
     *
     * @var null
     */
    public $event = NULL;
    /**
     * 事件通知传递的数据
     *
     * @var null
     */
    public $data = NULL;
    /**
     * 事件通知服务
     *
     * @var null
     */
    private $_observers = NULL;

    /**
     * DemoModel constructor.
     *
     * @param array $fields
     */
    public function __construct()
    {
        parent::__construct();
        $this->db = Database::instance();
        $this->_observers = new SplObjectStorage();
        $this->attach(new ObserverPlugin(__CLASS__));
    }

    /**
     * 新增
     * @param array $data
     *
     * @return int
     */
    public function insert(array $data): int
    {
        if (isset($data['name']))
            $data['name'] = preg_replace('#([\s]+)#is', '', $data['name']);
        if (empty($data['name'])) {
            $this->error_message = '名称不能为空';
            return 0;
        }
        $this->_inputPrepare($data);
        $data = Arr::filter_array($data, $this->_fields);
        return parent::insert($data);
    }

 /**
     * 修改
     *
     * @param array $data      新内容
     * @param int   $id 内容ID
     *
     * @return bool
     */
    public function edit(array $data, int $id): bool
    {
        $this->id = intval($id);
        if ($this->id <= 0) {
            $this->error_message = 'ID错误!';
            return FALSE;
        }
        $this->_inputPrepare($data);
        $this->data = $data;
        $this->event = 'before_edit';
        $this->notify();
        $this->data = Arr::filter_array($this->data, $this->_fields);
        if ($result = parent::update($this->data, $this->id)) {
            $this->event = 'after_edit';
            $this->notify();
        } else {
            $this->error_message = $this->getErrorMessage();
        }
        return $result;
    }

    /**
     * 列表
     *
     * @param array $where     检索条件
     * @param array $fields    列出字段
     *                         * @param string $order 排序方式
     * @param int   $page      当前页码
     * @param int   $page_size 每页数量
     *
     * @return array
     */
    public function ls(array $where, array $fields = NULL, string $order = '', int $page = 1, int $page_size = 16): array
    {
        $this->event = 'before_ls';
        $this->notify();
        //还可以用PDO::bind方法，格式如下
        //$this->db->query(Database::SELECT, $sql, false, ['id'=>123, 'status' => 0])->result();
        $data = $this->paging($where, $fields, $order, $page, $page_size, FALSE);
        if (!$data['data']) {
            $this->error = '没有数据';
            return [];
        }
        $this->_after_select($data['data'], TRUE);
        $this->data = $data['data'];
        $this->event = 'after_ls';
        $this->notify();
        return $data;
    }

    /**
     * 输入数据预处理
     *
     * @param array $r
     */
    protected function _inputPrepare(array &$r)
    {
    }

    /**
     * ls方法后续操作，输出前对数据做一些东东
     *
     * @param array $r
     */
    protected function output(array &$r)
    {
        // TODO: Implement output() method.
    }


    /**
     * 添加 observer
     *
     * @param SplObserver $observer
     */
    public function attach(SplObserver $observer)
    {
        $this->_observers->attach($observer);
    }

    /**
     * 移除 observer
     *
     * @param SplObserver $observer
     */
    public function detach(SplObserver $observer)
    {
        $this->_observers->detach($observer);
    }

    /**
     * 事件通知 observers
     */
    public function notify()
    {
        foreach ($this->_observers as $observer) {
            $observer->update($this);
        }
    }
}