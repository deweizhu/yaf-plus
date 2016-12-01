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
     * 给Twig模板分配自定义函数
     * @param $ctl            控制器，用$this传递过来即可
     * @param $funName        twig模板中使用的函数名
     * @param $InnerFunName   映射到静态方法名
     * @param $InnerClass     映射到类名
     */
    public static function twigFunction($ctl, string $funName, string $InnerFunName = NULL, string $InnerClass = NULL)
    {
        $InnerFunName === NULL AND $InnerFunName = $funName;
        $InnerClass === NULL AND $InnerClass = 'View';
        $ctl->getView()->getTwig()->addFunction(new Twig_SimpleFunction($funName,
            array($InnerClass, $InnerFunName),
            array('needs_context' => true)
        ));
    }

    /**
     * 使用方法：
     * <?php echo View::asset('/asset/gmu/zepto.min.js'); ?>
     * asset/*.js,*.css文件URI处理，避免文件变更后浏览器缓存问题
     * @param $uri
     * @return string
     */
    public static function asset(...$args)
    {
        if (func_num_args() === 1)
            $uri = $args[0];
        else
            list($context, $uri) = $args;
        if (strpos($uri, '://') !== FALSE || strpos($uri, '.min') !== FALSE) return $uri;
        $file = PUBPATH . $uri;
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