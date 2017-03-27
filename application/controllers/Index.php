<?php

/**
 *  默认首页
 *
 */
class IndexController extends Yaf_Controller_Abstract
{
    /**
     * 首页
     *
     * @return bool
     */
    public function indexAction()
    {
        if ($this->_request->isPost()) {

            $data = $_POST;
            if (empty($data['name'])) {
                return Response::jsonError('帐号不可为空！');
            }
            if (empty($data['password'])) {
                return Response::jsonError('密码不可为空！');
            }
            if (empty($data['password_confirm'])) {
                return Response::jsonError('确认密码不可为空！');
            }
            if ($data['password'] !== $data['password_confirm']) {
                return Response::jsonError('密码和确认密码不一致！');
            }
            $data['password'] = BcryptHasher::getInstance()->make($data['password']);
            unset($data['password_confirm']);

            $user = UserModel::instance()->save($data);
            if (empty($user)) {
                return Response::jsonError('保存用户失败');
            } else {
                return Response::jsonResult($user, '成功！');
            }

        }
        $this->_view->assign('title', '其实我是一个演员');
        $this->_view->assign('name', '施主，苦海无边，回头是岸');
        return TRUE;
    }

}