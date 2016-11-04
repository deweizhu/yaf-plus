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
        $this->_view->assign('title', '其实我是一个演员');
        $this->_view->assign('name', '闲坐小窗读周易，不觉春去已多时');
        return TRUE;
    }

}