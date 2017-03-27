<?php

/**
 *  错误控制器
 *
 * @author: ZDW
 * @date: 2015-11-23
 * @version: $Id: Error.php 12704 2015-11-23 12:47:16Z zdw $
 */
class ErrorController extends Yaf_Controller_Abstract
{

    public function errorAction($exception)
    {
        /* error occurs */
        switch ($exception->getCode()) {
            case YAF_ERR_NOTFOUND_MODULE:
            case YAF_ERR_NOTFOUND_CONTROLLER:
            case YAF_ERR_NOTFOUND_ACTION:
            case YAF_ERR_NOTFOUND_VIEW:
                echo 404, ":", $exception->getMessage();
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
