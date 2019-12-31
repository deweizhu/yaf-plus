<?php

/**
 *  错误控制器
 *
 * @author: Not well-known man
 *
 */
class ErrorController extends \Yaf\Controller_Abstract
{

    public function errorAction($exception)
    {
        /* error occurs */
        switch ($exception->getCode()) {
            case \Yaf\ERR\NOTFOUND\MODULE:
            case \Yaf\ERR\NOTFOUND\CONTROLLER:
            case \Yaf\ERR\NOTFOUND\ACTION:
            case \Yaf\ERR\NOTFOUND\VIEW:
                header('HTTP/1.1 404 Not Found', TRUE, 404);
                header('status: 404 Not Found', TRUE, 404);
                /*由于移动网络运营商会把404劫持，故改为302跳转*/
//                $this->redirect('/');
                break;
            default :
//                $message = $exception->getMessage();
                Elixir_Exception::handler($exception);
                break;
        }

        return FALSE;
    }
}
