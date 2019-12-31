<?php

/**
 * @name Bootstrap
 * @author Not well-known man
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:\Yaf\Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
require __DIR__ . '/Constant.php';
require __DIR__ . '/helpers.php';


class Bootstrap extends \Yaf\Bootstrap_Abstract
{


    /**
     * 加载vendor下的文件
     */
    public function _initLoader()
    {
        \Yaf\Loader::import(DOCROOT . '/vendor/autoload.php');
    }


    public function _initConfig()
    {
        /**
         * 设置默认时区
         *
         * @see  http://php.net/timezones
         */
        date_default_timezone_set('Asia/Shanghai');
        Elixir::init();
    }

    public function _initPlugin(\Yaf\Dispatcher $dispatcher)
    {
        //注册一个插件
        $objSamplePlugin = new SamplePlugin();
        $dispatcher->registerPlugin($objSamplePlugin);
    }

    public function _initRoute(\Yaf\Dispatcher $dispatcher)
    {
        //在这里注册自己的路由协议,默认使用简单路由
        $router = $dispatcher->getRouter();
    }

    public function _initView(\Yaf\Dispatcher $dispatcher)
    {
        //做API时不需要view层，故注释掉
//        $dispatcher->disableView();
//        return;
        //在这里注册自己的view控制器，例如smarty,firekylin
        $config = \Yaf\Application::app()->getConfig();
        $twig = new Template(VIEWPATH, $config->twig->toArray());
        $dispatcher->setView($twig);
//        Twig视图层可调用的“全局函数和变量”
        $_methods = get_class_methods('View');
        array_walk($_methods, function ($fun) use ($twig) {
            $twig->addFunction($fun, 'View::' . $fun);
        });
        $twig->addGlobal('app', $dispatcher->getRequest()->getModuleName() !== 'Index' ?? '');
        $twig->addGlobal('controller', $dispatcher->getRequest()->getControllerName());
        $twig->addGlobal('action', $dispatcher->getRequest()->getActionName());
        $twig->addGlobal('requesturi', $dispatcher->getRequest()->getRequestUri());
    }
}
