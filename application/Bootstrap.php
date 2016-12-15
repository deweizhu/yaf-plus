<?php

/**
 * @name Bootstrap
 * @author ZDW
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
require __DIR__ . '/Constant.php';

class Bootstrap extends Yaf_Bootstrap_Abstract
{

    public function _initConfig()
    {
        //运行环境
//        $environ = Yaf_Application::app()->environ();
//        Yaf_Registry::set('setting', new Yaf_Config_Ini(DOCROOT . '/conf/setting.ini', $environ));

        /**
         * 设置默认时区
         *
         * @see  http://php.net/timezones
         */
        date_default_timezone_set('Asia/Shanghai');

        Elixir::init();
//        Elixir::init([
//            'base_url'   => '/',
//            'index_file' => 'index.php',
//            'charset'    => 'utf-8',
//            'cache_dir'  => STORAGEPATH . '/cache',
//            'cache_life' => 60,
//        ]);

        //Cookie 设置
        $config = Yaf_Application::app()->getConfig();
        if ($cookie = $config->get('cookie')) {
            Cookie::$salt = $cookie->get('salt') ?: '123456';
            Cookie::$path = $cookie->get('path') ?: '/';
        }
        Cache::$default = $config->get('cache.default');
    }

    public function _initPlugin(Yaf_Dispatcher $dispatcher)
    {
        //注册一个插件
        $objSamplePlugin = new SamplePlugin();
        $dispatcher->registerPlugin($objSamplePlugin);
    }

    public function _initRoute(Yaf_Dispatcher $dispatcher)
    {
        //在这里注册自己的路由协议,默认使用简单路由
        $router = $dispatcher->getRouter();
    }

    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        //在这里注册自己的view控制器，例如smarty,firekylin
        $config = Yaf_Application::app()->getConfig();
        $dispatcher->setView(new Twig(VIEWPATH, $config->twig->toArray()));
    }
}
