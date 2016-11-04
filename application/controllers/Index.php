<?php

/**
 *  默认首页
 *
 */
class IndexController extends Yaf_Controller_Abstract
{

    /**
     * 首页
     * @return bool
     */
    public function indexAction()
    {
        $this->_view->assign('title', '标题测试');
        $this->_view->assign('name', '你好，我的名字是爱德华');
        return TRUE;
    }

}