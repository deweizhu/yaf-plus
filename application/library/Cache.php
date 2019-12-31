<?php
/**
 * Redis Cache类，依赖doctrine/cache
 * @author Not well-known man
 *
 */

use \Doctrine\Common\Cache\RedisCache;

class Cache extends RedisCache
{
    const DEFAULT_EXPIRE = 600;
    /**
     * @var RedisCache
     */
    public static $instance = NULL;

    /**
     * 单实例
     * @return RedisCache
     */
    public static function instance()
    {
        if (self::$instance)
            return self::$instance;

        $config = \Yaf\Application::app()->getConfig()->get('redis')->toArray();
        // Connect
        $redis = new \Redis;
        $redis->connect($config['host'], $config['port'], 10);
        $password = isset($config['password']) ?? NULL; //密码验证
        if (!empty($password))
            $redis->auth($password);
        //设置RedisCache
        $cacheDriver = new Cache();
        $cacheDriver->setRedis($redis);
        $cacheDriver->setNamespace($config['namespace']);
        self::$instance = $cacheDriver;
        return self::$instance;
    }


    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return $this->fetch($id);
    }

    /**
     * {@inheritdoc}
     */
    public function set($id, $data, $lifeTime = 0)
    {
        return $this->save($id, $data, $lifeTime);
    }

    public function save($id, $data, $lifeTime = 0)
    {
        //仅在生产环境使用cache
        if (\Yaf\ENVIRON === 'product' && $lifeTime === 0) {
            $lifeTime = \Yaf\Application::app()->getConfig()->get('redis.lifetime') ?: Cache::DEFAULT_EXPIRE;
        }
        return parent::save($id, $data, $lifeTime);
    }


    /**
     * 删除命名空间内所有cache
     * @param string $name  命名空间
     * @return bool
     */
    public function deleteAllByNameSpace(string $name)
    {
        $keys = $this->getRedis()->keys($name . '\[*');
        $success = true;
        foreach ($keys as $key) {
            if ($this->doDelete($key)) {
                continue;
            }
            $success = false;
        }
        return $success;
    }

    /**
     * Call redis functions
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        try
        {
            $rez = call_user_func_array(array($this->getRedis(), $name), $arguments);
        } catch (Exception $e)
        {
            throw new Elixir_Exception($e->getMessage());
        }
        return $rez;
    }

}