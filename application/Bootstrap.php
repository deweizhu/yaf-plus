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
        Yaf_Registry::set('site', new Yaf_Config_Ini(DOCROOT . '/conf/site.ini', YAF_ENVIRON));

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
        //composer
        is_file(DOCROOT . '/vendor/autoload.php') AND Yaf_Loader::import(DOCROOT . '/vendor/autoload.php');
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
        $twig = new Twig(VIEWPATH, $config->twig->toArray());
        $dispatcher->setView($twig);
        //Twig视图层可调用的“全局函数和变量”
        $_methods = get_class_methods('View');
        array_walk($_methods, function ($fun) use ($twig) {
            $twig->addFunction($fun, 'View::' . $fun);
        });
        $twig->addGlobal('site', Yaf_Registry::get('site')->toArray());
        $twig->addGlobal('app', $dispatcher->getRequest()->getModuleName() !== 'Index' ?? '' );
        $twig->addGlobal('controller', $dispatcher->getRequest()->getControllerName());
        $twig->addGlobal('action', $dispatcher->getRequest()->getActionName());
        $twig->addGlobal('requesturi', $dispatcher->getRequest()->getRequestUri());
    }
}
