<?php
/**
 * Session类，依赖symfony/http-foundation
 *
 * @author Not well-known man
 */


use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\SessionHandlerFactory;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler;

class Session extends Symfony\Component\HttpFoundation\Session\Session
{
    /**
     * @var Session
     */
    public static $instance = NULL;

    /**
     * Creates a singleton session of the given type. Some session types
     * (native, database) also support restarting a session by passing a
     * session id as the second parameter.
     *
     *     $session = Session::instance();
     *
     * [!!] [Session::write] will automatically be called when the request ends.
     *
     * @return  Session
     * @uses    Elixir::$config
     */
    public static function instance()
    {
        if ('fpm-fcgi' === PHP_SAPI)
            return self::instanceFPM();
        if (!isset(self::$instance)) {
//            不取配置，用默认值
//            $config = \Yaf\Application::app()->getConfig()->get('session')->get() ?? [];
//            self::$instance = $session = new Session_Redis($config);
            self::$instance = $session = new Session_Redis();
            // Write the session at shutdown
            register_shutdown_function(array($session, 'write'));
        }

        return self::$instance;
    }


    /**
     * 为FPM进程使用的Session
     * @return Session
     */
    protected static function instanceFPM()
    {
        if (self::$instance)
            return self::$instance;
//        $sessionStorage = SessionHandlerFactory::createHandler(Cache::instance()->getRedis());
        $sessionStorage = new RedisSessionHandler(Cache::instance()->getRedis());
        self::$instance = $session = new Session(new NativeSessionStorage([], $sessionStorage));
        $session->start();

        return self::$instance;
    }

    /**
     * 重新生成session id
     * @return  string
     */
    public function regenerate()
    {
        self::$instance->migrate();
        return self::$instance->getId();
    }
}