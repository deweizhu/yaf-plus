<?php

/**
 * view 帮助类
 * @author: ZDW
 * @date: 2015-11-26
 * @version: $Id: View.php 666 2015-12-08 12:00:05Z zhudewei $
 */
class View
{
    /**
     * 使用方法：
     * <?php echo View::asset('/asset/gmu/zepto.min.js'); ?>
     * asset/*.js,*.css文件URI处理，避免文件变更后浏览器缓存问题
     * @param $uri
     * @return string
     */
    public static function asset($uri)
    {
        $file = DOCROOT . $uri;
        if (!is_file($file)) {
            return '';
        }
        return $uri . '?_' . filemtime($file);
    }

    /**
     * 构建相对URI
     * @param array $param 原始URI参数
     * @param array $append 追加的参数
     * @param string $delimiter 定义分隔符
     * @return string
     */
    public static function buildUri(array $param, array $append, $delimiter = '-')
    {
        if (!$param) return '';
        if ($append) {
            $param = array_merge($param, $append);
        }
        $result = array();
        //分类只出现在第1位
        if (isset($param['c_o']) && $param['c_o'] !== '') {
            $result[] = $param['c_o'];
        }
        unset($param['c']);
        unset($param['c_o']);
        //地区出现在第1或2位
        if (isset($param['a']) && $param['a'] !== '') {
            $result[] = $param['a'];
        }
        unset($param['a']);
        foreach ($param as $k => $v) {
            if (!$v || ($k === 'p' && $v == 1)) continue;
            $result[] = $k . $v;
        }
        $count = count($result);
        if ($count > 0)
            $uri = implode($delimiter, $result);
        else
            $uri = '';
        return $uri;
    }

    /**
     * 构建动态查询URI
     * @param array $param 原始URI参数
     * @param array $append 追加的参数
     * @return string
     */
    public static function buildQueryUri(array $param, array $append = array())
    {
        if (!$param) return '';
        if ($append) {
            $param = array_merge($param, $append);
        }
        $uri = http_build_query(array_filter($param), '', '&');
        return $uri;
    }
}