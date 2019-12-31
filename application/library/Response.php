<?php
/**
 *  http输出响应
 *  注意：swoole不能使用exit，控制器中代码return false即可
 * @author Not well-known man
 */

use Symfony\Component\HttpFoundation\Response AS sfResponse;
use Symfony\Component\HttpFoundation\JsonResponse;


class Response
{

    /**
     *  API接口：生成JSON格式的正确消息
     *
     * @param array $data 数据
     * @param string $msg 提示消息
     * @param array $append
     * @return bool
     */
    public static function apiJsonResult($data, string $msg = '', array $append = array()): bool
    {
        $content = array('error' => 0, 'msg' => $msg);
        $content['data'] = $data;
        if (!empty($append)) {
            foreach ($append AS $key => $val) {
                $content[$key] = $val;
            }
        }
        return self::json($content);
    }

    /**
     *  API接口：创建一个JSON格式的错误信息
     *
     * @param int $error 错误代码
     * @param string $msg 提示消息
     * @return bool
     */
    public static function apiJsonError(int $error, string $msg): bool
    {
        $content = array('error' => $error, 'msg' => $msg);
        $content['data'] = [];
        return self::json($content);
    }


    /**
     * 输出HTML内容
     * @param string $content
     * @param int $code
     * @return bool
     */
    public static function html(string $content, int $code = sfResponse::HTTP_OK)
    {
        $response = new sfResponse($content, $code, array('content-type' => 'text/html'));
        $response->send();
        return FALSE;
    }

    /**
     * 输出JSON
     * @param array $data
     * @param int $code
     * @return bool
     */
    public static function json(array $data, int $code = sfResponse::HTTP_OK)
    {
        $response = new JsonResponse();
        $response->setData($data);
        //跨域 Jquery + Zeptojs jsonp
        if (Request::getQuery('jsoncallback'))
            $response->setCallback('jsoncallback');
        elseif (Request::getQuery('callback'))
            $response->setCallback('callback');
        $response->send();
        return FALSE;
    }

    /**
     * 输出文本
     * @param string $content
     * @param int $code
     * @return bool
     */
    public static function text(string $content, int $code = sfResponse::HTTP_OK)
    {
        $response = new sfResponse($content, $code, array('content-type' => 'text/html'));
        $response->send();
        return FALSE;
    }

    /**
     * @param string $msg
     * @throws Exception
     * @deprecated 暂时不建议使用
     */
    public static function exit(string $msg)
    {
        //php-fpm的环境
        if (PHP_SAPI === 'fpm-fcgi') {
            exit($msg);
        } //swoole的环境
        else {
            throw new Exception($msg);
        }
    }

    /**
     * 设置响应输出头信息
     * @param string $key
     * @param string $value
     */
    public static function header(string $key, string $value)
    {
        if (PHP_SWOOLE) {
            HttpServer::$response->header($key, $value);
            return;
        }
//        if (!headers_sent()) {
        header($key . ':' . $value);
//        }
    }
}