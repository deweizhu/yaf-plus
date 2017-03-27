<?php

/**
 *  用户表模型
 *
 */
class UserModel extends Model implements SplSubject
{
    protected $_table = 'users';
    protected $_primary = 'id';
    protected $_softDelete = TRUE;
    protected $_fields = array(
        'id',
        'name',
        'password',
        'nick_name',
        'avatar',
        'phone',
        'email',
        'real_name',
        'sex',
        'province',
        'city',
        'area',
        'birth_year',
        'birth_month',
        'birth_day',
        'invite_uid',
        'invite_code',
        'signature',
        'token',
        'last_login_at',
        'created_at',
        'deleted_at'
    );

    protected $_hidden = ['password'];
    protected $_create_autofill = ['created_at' => TIMENOW];


    /**
     * 事件通知服务
     *
     * @var null
     */
    private $_observers = NULL;

    public function __construct()
    {
        parent::__construct();
//         $this->db = Database::instance();
        $this->_observers = new SplObjectStorage();
    }

    /**
     * 根据用户名取用户ID
     *
     * @param string $username
     *
     * @return int
     */
    public function userid(string $username): int
    {
        $val = DB::select('id')->from($this->_table)->where('name', '=', $username)
            ->limit(1)->cached()->execute($this->db)->get('id');
        return $val ? intval($val) : 0;
    }

    /**
     * 根据用户ID获取用户名
     *
     * @param int $user_id
     *
     * @return string
     */
    public function username(int $user_id): string
    {
        $val = DB::select('name')->from($this->_table)->where('id', '=', $user_id)
            ->limit(1)->cached()->execute($this->db)->get('name');
        return $val ?: '';
    }

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
