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

    /**
     * 事件通知
     * @var null
     */
    public $event = NULL;
    /**
     * 事件通知传递的数据
     * @var null
     */
    public $data = NULL;
    /**
     * 事件通知服务
     * @var null
     */
    private $_observers = NULL;

    /**
     * DemoModel constructor.
     * @param array $fields
     */
    public function __construct()
    {
        parent::__construct();
        $this->_observers = new SplObjectStorage();
        $this->attach(new ObserverPlugin(__CLASS__));
    }


    /**
     * 获取内容列表
     * @param array $where 检索条件
     * @param array $fields 列出字段
     * * @param string $order 排序方式
     * @param int $page 当前页码
     * @param int $page_size 每页数量
     * @return array
     */
    public function ls(array $where, array $fields = NULL, string $order = '', int $page = 1, int $page_size = 16): array
    {
        $this->event = 'before_ls';
        $this->notify();
        $data = $this->paging($where, $fields, $order, $page, $page_size, FALSE);
        if (!$data) {
            $this->error = '没有数据';
            return [];
        }
        $this->_after_select($data, TRUE);
        $this->data = $data;
        $this->event = 'after_ls';
        $this->notify();
        return $data;
    }

    /**
     * ls方法后续操作，输出前对数据做一些东东
     * @param array $r
     */
    protected function output(array &$r)
    {
        // TODO: Implement output() method.
    }

    /**
     * [私有]查询条件
     * @param Database_Query_Builder_Select $where
     * @param array $option
     */
    private function _where(Database_Query_Builder_Select &$query, array $option)
    {
        if (!$option)
            return;
        if (isset($option['keywords']) && $option['keywords']) {
            $query->where(DB::expr('INSTR(`title`, ?1)', ['?1' => $option['keywords']]), '>', 0);
        }
        return;
    }

    /**
     * 添加 observer
     * @param SplObserver $observer
     */
    public function attach(SplObserver $observer)
    {
        $this->_observers->attach($observer);
    }

    /**
     * 移除 observer
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