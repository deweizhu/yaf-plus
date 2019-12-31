<?php

/**
 * Debug日志类
 * 用法1：
 *   日志生成在 'application/logs/'目录
 *   Zlog::write($data);
 *
 * 用法2：
 *   Zlog::$console = false;
 *   Zlog::$logfile = DOCROOT . '/debug.txt';
 *   Zlog::write($data);
 *
 * @author    Not well-known man
 *
 *
 */
class Zlog
{
    /**
     * 　日志文件
     */
    static $logfile = '';
    /**
     *  是否控制台显示
     */
    static $console = FALSE;

    /**
     * 日志记录
     * params mix $info
     * return void
     **/
    public static function write($info)
    {
        if (self::$console === FALSE && self::$logfile === '')
            self::$logfile = APPPATH . '/logs/' . date('Y-m-d') . '.log';
        if (is_object($info) || is_array($info)) {
            $info_text = var_export($info, TRUE);
        } elseif (is_bool($info)) {
            $info_text = $info ? 'true' : 'false';
        } else {
            $info_text = $info;
        }
        $info_text = '[' . date('Y-m-d H:i:s') . '] ' . $info_text;
        if (!empty(self::$logfile)) {
            error_log($info_text . "\r\n", 3, self::$logfile);
        } else error_log($info_text);
        if (self::$console) echo "\n" . $info_text . "\n";
    }
}