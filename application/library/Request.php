<?php
/**
 * 由于swoole+yaf无法使用$_GET/$_POST全局变量，故此用此类实现
 *
 * @author Not well-known man
 */

use Symfony\Component\HttpFoundation\Request AS sfRequest;

/**
 * Class Request
 */
class Request
{
    protected $mimeType = [
        'xml'   => 'application/xml,text/xml,application/x-xml',
        'json'  => 'application/json,text/x-json,application/jsonrequest,text/json',
        'js'    => 'text/javascript,application/javascript,application/x-javascript',
        'css'   => 'text/css',
        'rss'   => 'application/rss+xml',
        'yaml'  => 'application/x-yaml,text/yaml',
        'atom'  => 'application/atom+xml',
        'pdf'   => 'application/pdf',
        'text'  => 'text/plain',
        'image' => 'image/png,image/jpg,image/jpeg,image/pjpeg,image/gif,image/webp,image/*',
        'csv'   => 'text/csv',
        'html'  => 'text/html,application/xhtml+xml,*/*',
    ];

    /**
     * @var Symfony\Component\HttpFoundation\Request
     */
    static $request = NULL;

    /**
     * 初始化全局变量
     * @return Symfony\Component\HttpFoundation\Request
     */
    public static function create()
    {
        if ('cli' === PHP_SAPI) {
            $sw_request = Yaf\Registry::get('sw_request');
            if (isset($sw_request->get))
                $_GET = $sw_request->get;
            if (isset($sw_request->post))
                $_POST = $sw_request->post;
            if (isset($sw_request->cookie))
                $_COOKIE = $sw_request->cookie;
            if (isset($sw_request->server))
                $_SERVER = $sw_request->server;
            if (isset($sw_request->files))
                $_FILES = $sw_request->files;
            self::$request = new sfRequest($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
            return self::$request;
        }

        if (self::$request === NULL)
            self::$request = sfRequest::createFromGlobals();
        return self::$request;
    }

    /**
     * $_GET 方法
     * @return Symfony\Component\HttpFoundation\ParameterBag
     */
    public static function query()
    {
        return Request::create()->query;
    }

    /**
     * $_POST 方法
     * @return Symfony\Component\HttpFoundation\ParameterBag
     */
    public static function post()
    {
        return Request::create()->request;
    }


    /**
     * $_GET 方法
     * @param string $name 参数名
     * @param string $default 默认
     * @return string
     */
    public static function getQuery(string $name,string $default = ''): ?string
    {
        if (PHP_FPM) {
            return $_GET[$name] ?? $default;
        }
        $_req = \Yaf\Registry::get('sw_request');
        if ($_req && $_req->get)
            return $_req->get[$name] ?? $default;
        return NULL;
    }

    /**
     * $_POST方法
     * @param string $name
     * @param string $default 默认
     * @return string
     */
    public static function getPost(string $name,string $default = ''): ?string
    {
        if (PHP_FPM) {
            $request = new Request();
            return $request->getPostData($name) ?? $default;
        }
        $_req = \Yaf\Registry::get('sw_request');
        if ($_req && $_req->post)
            return $_req->post[$name] ?? $default;
        return NULL;
    }

    /**
     * 取$_GET数组所有内容
     * @return array
     */
    public static function getQueryAll(): array
    {
        if (PHP_FPM) {
            return $_GET;
        }
        $_req = \Yaf\Registry::get('sw_request');
        if ($_req && $_req->get)
            return $_req->get ?? [];
        return [];
    }

    /**
     * $_FILES 方法
     * @param string $name 参数名
     * @return array
     */
    public static function getFiles(string $name): ?array
    {
        if ('fpm-fcgi' === PHP_SAPI) {
            return $_FILES[$name] ?? [];
        }
        $_req = \Yaf\Registry::get('sw_request');
        if ($_req && $_req->file)
            return $_req->file[$name] ?? [];
        return NULL;
    }


    /**
     * 获取post参数
     * @param string $name
     * @return mixed
     */
    public function getPostData(string $name){
        $type = $this->type();
        static $data;
        if($type == 'json' && empty($_POST)){
            if (!$data) $data = json_decode(file_get_contents('php://input'),1);
            return $data[$name] ?? '';
        }else{
            return $_POST[$name] ?? '';
        }
    }

    /**
     * 当前请求的资源类型
     * @access public
     * @return false|string
     */
    public function type() :string{
        $accept = $this->server('HTTP_ACCEPT');
        if (empty($accept)) return '';

        foreach ($this->mimeType as $key => $val) {
            $array = explode(',', $val);
            foreach ($array as $k => $v) {
                if (stristr($accept, $v)) return $key;
            }
        }
        return '';
    }

    /**
     * 获取server参数
     * @access public
     * @param string $name 数据名称
     * @param string $default 默认值
     * @return string
     */
    public function server(string $name = '', $default = null) :string{
        $server = $_SERVER;
        if (empty($name)) return $server;

        $name = strtoupper($name);
        return isset($server[$name]) ? $server[$name] : $default;
    }
}