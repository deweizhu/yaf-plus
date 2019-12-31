<?php
/**
 *  Curl 封装类
 * @author Not well-known man
 */

class Curlx
{


    /**
     * 上传图片（或其它文件）
     *
     * @param string $url 接收文件的地址
     * @param string $filepath 本地文件路径
     * @param string $basename 基本文件名
     * @param string $mimetype 文件类型，默认image/jpeg
     * @return string
     */
    public static function upfile(string $url, string $filepath, string $basename, string $mimetype = 'image/jpeg'):
    string
    {
        $filename = new \CURLFile($filepath, $mimetype, $basename);
        $data = [
            'file' => $filename,
            'name' => $basename
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ?? '';
    }

    /**
     * CURL GET获取数据
     *
     * @param string $url
     * @param array $options
     * @return bool|string
     */
    public static function get(string $url = '', array $options = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * CURL POST发送数据
     * 加强版，支持超大BODY返回
     *
     * @param string $url
     * @param array $params
     * @param array|NULL $options
     * @return array
     */
    public static function postX(string $url, array $params, array $options = NULL)
    {
        $ch = curl_init();
        self::_setOption($ch, $options);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($params) ? 1: 0);
        $params = http_build_query($params);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($ch);
        $errorCode = curl_errno($ch);
        $sentHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $headerSize);
        $body = substr($result, $headerSize);
        curl_close($ch);
        //{"code":"0000","msg":"success"}
        return array($errorCode, $body);
    }

    public static function post(string $url, array $params, array $options = NULL)
    {
        $ch = curl_init();
        self::_setOption($ch, $options);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($params) ? 1: 0);
        $params = http_build_query($params);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $body = curl_exec($ch);
        $errorCode = curl_errno($ch);
        curl_close($ch);
        //{"code":"0000","msg":"success"}
        return array($errorCode, $body);
    }

    /**
     * CURL POST发送数据
     *
     * @param string $url
     * @param string $params
     * @param array|NULL $options
     * @return array
     */
    public static function postString(string $url, string $params, array $options = NULL)
    {
        $ch = curl_init();
        self::_setOption($ch, $options);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $body = curl_exec($ch);
        $errorCode = curl_errno($ch);
        curl_close($ch);
        //{"code":"0000","msg":"success"}
        return array($errorCode, $body);
    }

    /**
     * @param resource $ch
     * @param array|NULL $options
     */
    private static function _setOption($ch, array $options = NULL)
    {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if ($options === NULL) {
            $options = array();
        }
        if (isset($options['cookie']) && is_array($options['cookie'])) {
            $cookieArr = array();
            foreach ($options['cookie'] as $key => $value) {
                $cookieArr[] = $key . '=' . $value;
            }
            $cookie = implode('; ', $cookieArr);
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        $timeout = 30;
        if (isset($options['timeout'])) {
            $timeout = $options['timeout'];
        }
        if (isset($options['ua'])) {
            curl_setopt($ch, CURLOPT_USERAGENT, $options['ua']);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if (isset($options['header'])) {
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            $header = array();
            foreach ($options['header'] as $k => $v) {
                $header[] = $k . ': ' . $v;
            }
            $header[] = 'Expect:';//disable HTTP/1.1 100 Continue
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
    }
}