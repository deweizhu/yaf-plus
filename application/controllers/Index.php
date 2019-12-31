<?php

/**
 *  默认首页
 *
 */
class IndexController extends Yaf\Controller_Abstract
{

    /**
     * 首页
     *
     * @return bool
     */
    public function indexAction()
    {
        $this->_view->assign('title', '其实我是一个演员');
        $this->_view->assign('name', '施主，苦海无边，回头是岸');

        return TRUE;
    }

    /**
     *
     * @return bool
     */
    public function phpinfoAction()
    {
        phpinfo();

        return FALSE;
    }

}