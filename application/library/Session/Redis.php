<?php

/**
 * @author Not well-known man
 *
 * session redis or memcache
 */
class Session_Redis extends Session_Core
{
    /**
     * @var Cache
     */
    protected $_cache; // Redis instance


    protected $_session_id; // The current session id

    protected $_update_id; // The old session id


    /**
     *
     * @param array $config
     * @param string $id
     */
    public function __construct($config = NULL, $id = NULL)
    {
        $this->_cache = Cache::instance();

        parent::__construct($config, $id);
    }

    /**
     * {@inheritDoc}
     * @see Elixir_Session::id()
     */
    public function id()
    {
        return $this->_session_id;
    }

    /**
     * 保存session
     * @return bool
     */
    public function save()
    {
        try
        {
            return $this->_write();
        }
        catch (Exception $e)
        {
            // Log & ignore all errors when a write fails
            Elixir::$log->add(Log::ERROR, Elixir_Exception::text($e))->write();

            return FALSE;
        }
    }

    /**
     * 读取
     * @see Elixir_Session::_read()
     */
    protected function _read($id = NULL)
    {
        if ($id OR $id = Cookie::get($this->_name)) {
            $result = $this->_cache->get($id);
            if (!empty($result)) {
                // Set the current session id
                $this->_session_id = $this->_update_id = $id;
                // Return the contents
                return $result;
            }
        }
        // Create a new session id
        $this->_regenerate();
        return NULL;
    }

    protected function _regenerate()
    {
        // Create a new session id
        $this->_session_id = str_replace('.', '-', uniqid(NULL, TRUE));
        // 手动写数据
        if (PHP_SWOOLE) {
            $this->write();
        }
        return $this->_session_id;
    }

    protected function _write()
    {
        if (!empty($this->_cache->get($this->_session_id))) {
            $this->_cache->expire($this->_session_id, $this->_lifetime);
        }

        $this->_cache->set($this->_session_id, $this->__toString(), $this->_lifetime);
        // Update the cookie with the new session id
        Cookie::set($this->_name, $this->_session_id, $this->_lifetime);

        return TRUE;
    }

    /**
     * @return  bool
     */
    protected function _restart()
    {
        $this->_regenerate();
        return TRUE;
    }

    protected function _destroy()
    {
        if ($this->_update_id === NULL) {
            // Session has not been created yet
            return TRUE;
        }
        $this->_cache->delete($this->_update_id);
        // Delete the cookie
        Cookie::delete($this->_name);
        return TRUE;
    }

}

