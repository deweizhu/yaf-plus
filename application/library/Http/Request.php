<?php
/**
 *  HttpRequest类库,支持301，302转向跟踪
 * ============================================================================
 * 版权所有 (C) 2009 Dewei<zdw163@hotmail.com>，并保留所有权利。
 * ----------------------------------------------------------------------------
 * This is NOT a freeware, use is subject to license terms
 * ============================================================================
 * 用法：
 *   $header = array(
 *       'Accept' => $_SERVER['HTTP_ACCEPT'],
 *       'Accept-Language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'],
 *      'DNT' => 1
 *   );
 *   $net = new HttpRequest();
 *   //$net->setUserAgent($_SERVER['HTTP_USER_AGENT']);
 *   //$net->setHeaders($header);
 *   //$net->setReferer('http://www.baidu.com');
 *   $net->post('http://www.baidu.com', $params);
 *   $net->get('http://www.baidu.com');
 *   $body = $net->body();
 */
@set_time_limit(0);

// HttpRequest
class Http_Request
{
    public static $instances = NULL;
    // 目标网站无法打开时返回的错误代码
    const ERROR_CONNECT_FAILURE = 600;
    // 自定义 UserAgent 字符串
    private $_user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 BIDUBrowser/6.x Safari/537.31';
    private $_url, $_method, $_timeout;
    private $_host, $_port, $_path, $_query, $_referer;
    private $_header;
    private $_body;
    private $_params;
    private $_cookiefile = '';
    private $_cookie = '';

    //自定义头信息
    private $_headers = array();

    // __construct
    public function __construct()
    {
    }

    public static function instance()
    {
        if (!isset(self::$instances)) {
            self::$instances = new Http_Request();
        }
        return self::$instances;
    }

    /**
     * 快速发送请求
     *
     * @param string $url 请求的URL
     * @param array|string $post post数据
     * @param int $timeout 执行超时时间
     * @return array
     */
    public static function quick($url, $post = NULL, $timeout = 40)
    {
        static $d = NULL;
        if (!$d)
            $d = new Http_Request();
        if ($post)
            $d->post($url, $post, $timeout);
        else
            $d->get($url);
        return array(
            'httpcode' => $d->status(),
            'content'  => $d->body()
        );
    }

    // header
    public function header()
    {
        return $this->_header;
    }

    // body
    public function body()
    {
        return $this->_body;
    }

    // status
    public function status($header = NULL)
    {
        if (empty($header)) {
            $header = $this->_header;
        }
        if (preg_match('#(.+) (\d+) (.+)([\r\n]{0,1})#i', $header, $status)) {
            return (int)$status[2];
        } else {
            return self::ERROR_CONNECT_FAILURE;
        }
    }

    /**
     * 获取当前URL
     * @return mixed
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /*
     * 设置http_user_agent
     */
    public function setUserAgent($user_agent)
    {
        $this->_user_agent = $user_agent;
        return $this;
    }

    /**
     * 设置COOKIE文件
     * @param string $cookiefile
     */
    public function setCookiefile($cookiefile)
    {
        $this->_cookiefile = $cookiefile;
        return $this;
    }

    /**
     * 设定HTTP请求中"Cookie: "部分的内容。多个cookie用分号分隔，分号后带一个空格(例如， "fruit=apple; colour=red")。
     * @param string $cookie
     */
    public function setCookie($cookie)
    {
        $this->_cookie = $cookie;
        return $this;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        $this->_headers = $headers;
        return $this;
    }

    /**
     * @param mixed $referer
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;
        return $this;
    }

    /**
     * get 请求
     * @param string $url
     * @param int $timeout
     * @return $this
     */
    public function get($url, $timeout = 30)
    {
        if (!$this->parseURL($url)) {
            die('url not empty.');
        }
        $this->_url = $url;
        $this->_method = 'GET';
        $this->_timeout = empty($timeout) ? 30 : $timeout;
        if (function_exists('curl_init')) {
            $this->curlRequest();
        } elseif (function_exists('fsockopen')) {
            $this->socketRequest();
        } else {
            die('Not found curl & fsockopen.');
        }
        return $this;
    }

    /**
     * get 请求
     * @param string $url
     * @param string|array $data
     * @param int $timeout
     * @return $this
     */
    public function post($url, $data, $timeout = 30)
    {
        if (!$this->parseURL($url)) {
            die('url not empty.');
        }
        $this->_url = $url;
        $this->_method = 'POST';
        $this->_timeout = empty($timeout) ? 30 : $timeout;
        $this->_params = $data;
        if (function_exists('curl_init')) {
            $this->curlRequest();
        } elseif (function_exists('fsockopen')) {
            $this->socketRequest();
        } else {
            die('Not found curl & fsockopen.');
        }
        return $this;
    }

    /**
     * curl 请求
     * @return bool
     */
    private function curlRequest()
    {
        $header = NULL;
        $body = NULL;
        $QueryStr = NULL;
        $ch = curl_init($this->_url);
        if (strncmp($this->_url, 'https', 5) === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $opt = array(
            CURLOPT_TIMEOUT        => $this->_timeout,
            CURLOPT_HEADER         => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_USERAGENT      => $this->_user_agent,
            CURLOPT_REFERER        => $this->_referer,
            CURLOPT_FOLLOWLOCATION => FALSE,
            CURLOPT_MAXREDIRS      => 100,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_HTTPHEADER     => $this->_headers
        );
        if ($this->_cookiefile !== '') {
            $opt[CURLOPT_COOKIEFILE] = $this->_cookiefile;
            $opt[CURLOPT_COOKIEJAR] = $this->_cookiefile;
        }
        curl_setopt_array($ch, $opt);
        if ($this->_cookie !== '') {
            curl_setopt($ch, CURLOPT_COOKIE, $this->_cookie);
        }
        if ($this->_method == 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
        } else {
            if (is_array($this->_params)) {
                $QueryStr = http_build_query($this->_params);
            } else {
                $QueryStr = $this->_params;
            }
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $QueryStr);
        }
        $fp = curl_exec($ch);
        curl_close($ch);
        if (!$fp) {
            return FALSE;
        }
        $i = 0;
        $length = strlen($fp);
        // 读取 header
        do {
            $header .= substr($fp, $i, 1);
            $i++;
        } while (!preg_match("/\r\n\r\n$/", $header));
        // 遇到跳转，执行跟踪跳转
        if ($this->redirect($header)) {
            return TRUE;
        }
        // 读取内容
        do {
            $body .= substr($fp, $i, 4096);
            $i = $i + 4096;
        } while ($length >= $i);
        unset($fp, $length, $i);
        $this->_header = $header;
        $this->_body = $body;
        return TRUE;
    }


    /**
     * socket请求
     * @return bool
     */
    private function socketRequest()
    {
        $header = NULL;
        $body = NULL;
        $QueryStr = NULL;
        $fp = fsockopen($this->_host, $this->_port, $errno, $errstr, $this->_timeout);
        if (!$fp) {
            return FALSE;
        }
        $SendStr = "{$this->_method} {$this->_path}{$this->_query} HTTP/1.0\r\n";
        $SendStr .= "Host:{$this->_host}:{$this->_port}\r\n";
        $SendStr .= "Referer:{$this->_referer}\r\n";
        $SendStr .= "User-Agent: " . $this->_user_agent . "\r\n";
        if (!empty($this->_headers)) {
            foreach ($this->_headers as $k => $v) {
                $SendStr .= $k . ':' . $v . "\r\n";
            }
        }
        //如果是POST方法，分析参数
        if ($this->_method == 'POST') {
            //判断参数是否是数组，循环出查询字符串
            if (is_array($this->_params)) {
                $QueryStr = http_build_query($this->_params);
            } else {
                $QueryStr = $this->_params;
            }
            $length = strlen($QueryStr);
            $SendStr .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $SendStr .= "Content-Length: {$length}\r\n";
        }
        $SendStr .= "Connection: Close\r\n\r\n";
        if (strlen($QueryStr) > 0) {
            $SendStr .= $QueryStr . "\r\n";
        }
        fputs($fp, $SendStr);
        // 读取 header
        do {
            $header .= fread($fp, 1);
        } while (!preg_match("/\r\n\r\n$/", $header));
        // 遇到跳转，执行跟踪跳转
        if ($this->redirect($header)) {
            return TRUE;
        }
        // 读取内容
        while (!feof($fp)) {
            $body .= fread($fp, 4096);
        }
        fclose($fp);
        $this->_header = $header;
        $this->_body = $body;
        return TRUE;
    }


    // parseURL
    private function parseURL($url)
    {
        if (!$url) return FALSE;
        $aUrl = parse_url($url);
        $this->_host = $aUrl['host'];
        $this->_port = empty($aUrl['port']) ? 80 : (int)$aUrl['port'];
        $this->_path = empty($aUrl['path']) ? '/' : (string)$aUrl['path'];
        $this->_query = isset($aUrl['query']) && strlen($aUrl['query']) > 0 ? '?' . $aUrl['query'] : NULL;
        // $this->_referer = 'http://' . $aUrl['host'];
        return TRUE;
    }

    // redirect
    private function redirect($header)
    {
        if (in_array($this->status($header), array(301, 302))) {
//            if (preg_match('#Location\:(.+)([\r\n]{0,1})#i', $header, $regs)) {
            if (preg_match('#Location:(.+)[\s]+#i', $header, $regs)) {
                $this->connect(trim($regs[1]), $this->_method, $this->_timeout);
                $this->execute();
                return TRUE;
            }
        } else {
            return FALSE;
        }
    }
}

