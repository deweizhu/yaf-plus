<?php
/**
 *  一些常量
 */

define('PHP_SWOOLE', 'cli' === PHP_SAPI); //swoole运行模式？
define('PHP_FPM', 'fpm-fcgi' === PHP_SAPI); //FPM运行模式？

define('TIME_FORMAT', 'Y-m-d H:i:s');//时间格式
define('DATE_FORMAT', 'Y-m-d');//日期格式
define('TIMENOW', $_SERVER['REQUEST_TIME']); // 当前 Unix 时间戳

// 定义一些有用的内容与相关的环境
define('IPADDRESS', isset($_SERVER ['HTTP_CLIENT_IP']) ? $_SERVER["HTTP_CLIENT_IP"] :
    (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] :
        (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '0.0.0.0')
    )
);
define('REQ_PROTOCOL', (isset($_SERVER ['HTTPS']) && ($_SERVER ['HTTPS'] == 'on' || $_SERVER ['HTTPS'] == '1') ? 'https' : 'http'));
define('USER_AGENT', $_SERVER['HTTP_USER_AGENT'] ?? '');
define('REFERRER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
define('HTTP_HOST', isset($_SERVER ['HTTP_HOST']) && isset($_SERVER ['SERVER_NAME']) ?
    ($_SERVER ['HTTP_HOST'] ?: $_SERVER ['SERVER_NAME']) : ''
);