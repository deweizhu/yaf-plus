<?php

/**
 *  输出JSON/buffer等
 */
class Response
{
    /**
     *   生成JSON格式的正确消息
     *
     * @param string|array $content
     * @param string       $message
     * @param array        $append
     */
    public static function jsonResult($content, string $message = '', array $append = array())
    {
        self::jsonResponse($content, 0, $message, $append);
    }

    /**
     * 创建一个JSON格式的错误信息
     *
     * @param string $msg
     */
    public static function jsonError(string $msg, array $append = array())
    {
        self::jsonResponse('', 1, $msg, $append);
    }

    /**
     * 创建一个JSON格式的数据
     *
     * @param   string|array $content
     * @param   int          $error
     * @param   string       $message
     * @param   array        $append
     *
     * @return  void
     */
    private static function jsonResponse($content = '', int $error = 0, string $message = '', array $append = array())
    {
        $res = array('error' => $error, 'message' => $message);
        if ($error !== 1) $res['content'] = $content;
        if (!empty($append)) {
            foreach ($append AS $key => $val) {
                $res[$key] = $val;
            }
        }
        $val = json_encode($res);
        //Jquery + Zeptojs jsonp
        if (isset($_GET['jsoncallback'])) {
            $val = $_GET['jsoncallback'] . '(' . $val . ')';
        } elseif (isset($_GET['callback'])) {
            $val = $_GET['callback'] . '(' . $val . ')';
        }
        exit($val);
    }

    /**
     *  API接口：生成JSON格式的正确消息
     *
     * @param array  $data 数据
     * @param string $msg  提示消息
     * @param array  $append
     */
    public static function apiJsonResult(array $data, string $msg = '', array $append = array())
    {
        self::apiJsonResponse($data, 0, $msg, $append);
    }

    /**
     *  API接口：创建一个JSON格式的错误信息
     *
     * @param int    $error 错误代码
     * @param string $msg   提示消息
     */
    public static function apiJsonError(int $error, string $msg)
    {
        self::apiJsonResponse([], $error, $msg);
    }

    /**
     * 创建一个JSON格式的数据
     *
     * @access  public
     *
     * @param   array  $data
     * @param   int    $error
     * @param   string $msg
     *
     * @return  void
     */
    private static function apiJsonResponse(array $data = [], int $error = 200, string $msg = '', array $append = array())
    {

        $res = array('error' => $error, 'msg' => $msg);
        if (!empty($data))
            $res['data'] = $data;
        if (!empty($append)) {
            foreach ($append AS $key => $val) {
                $res[$key] = $val;
            }
        }
        $val = json_encode($res);
        //Jquery + Zeptojs jsonp
        if (isset($_GET['jsoncallback'])) {
            $val = $_GET['jsoncallback'] . '(' . $val . ')';
        } elseif (isset($_GET['callback'])) {
            $val = $_GET['callback'] . '(' . $val . ')';
        }
        exit($val);
    }

    /**
     *  protobuf：返回提示消息
     *
     * @param string $code 错误代码
     * @param string $msg  提示消息
     */
    public static function protobufResponse($code, $msg)
    {
        if (!headers_sent()) {
            header('Content-Type:application/octet-stream');
            header('code:' . intval($code));
        }
        $pbres = new Proto_ErrorModel();
        $pbres->setCode(intval($code));
        $pbres->setMsg($msg);
        echo $pbres->serializeToString();
        exit();
    }
}