<?php

/**
 * view 帮助类
 *
 * @author    知名不具
 * @date      : 2015-11-26
 * @version   : $Id: View.php 666 2015-12-08 12:00:05Z zhudewei $
 */
class View
{

    /**
     * 给Twig模板分配自定义函数
     *
     * @param object $ctl          控制器，用$this传递过来即可
     * @param string $funName      twig模板中使用的函数名
     * @param string $InnerFunName 映射到静态方法名
     * @param string $InnerClass   映射到类名
     * @param bool   $needsContext 是否包含上下文变量
     */
    public static function twigFunction($ctl, string $funName, string $InnerFunName = NULL, string $InnerClass = NULL,
                                        bool $needsContext = FALSE)
    {
        $InnerFunName === NULL AND $InnerFunName = $funName;
        $InnerClass === NULL AND $InnerClass = 'View';
        $ctl->getView()->getTwig()->addFunction(new Twig_SimpleFunction($funName,
            array($InnerClass, $InnerFunName),
            array('needs_context' => $needsContext)
        ));
    }

    /**
     * 使用方法：
     * <?php echo View::asset('/asset/gmu/zepto.min.js'); ?>
     * asset/*.js,*.css文件URI处理，避免文件变更后浏览器缓存问题
     *
     * @param $uri
     *
     * @return string
     */
    public static function asset(...$args): string
    {
        if (func_num_args() === 1)
            $uri = $args[0];
        else
            list($context, $uri) = $args;
        if (strpos($uri, '://') !== FALSE || strpos($uri, '.min') !== FALSE) return $uri;
        $file = PUBPATH . $uri;
        if (!is_file($file) && !is_file($file.'.js')) {
            return '';
        }
        if(is_file($file.'.js')) {
            return $uri;
        }
        return $uri . '?_' . filemtime($file);
    }

    /**
     * 从模型中调用get方法取值
     *
     * @param array  ...$args
     * @param string $model 模型名称
     * @param int    $id    主键值
     * @param string $filed 字段列名
     *
     * @return string
     */
    public static function model_get(...$args): string
    {
        if (func_num_args() === 3)
            list($model, $id, $filed) = $args;
        else
            list($context, $model, $id, $filed) = $args;
        if (strpos($model, '_') !== FALSE) {
            $arr = explode('_', $model);
            foreach ($arr as &$v)
                $v = ucfirst($v);
            unset($v);
            $class = implode('_', $arr) . 'Model';
        } else {
            $class = ucfirst($model) . 'Model';
        }
        $id += 0;
        return $class::instance()->get($id, $filed, TRUE);
    }

    /**
     *
     */
    public function permission()
    {

    }

    /**
     * 构建相对URI
     *
     * @param array  $param     原始URI参数
     * @param array  $append    追加的参数
     * @param string $delimiter 定义分隔符
     *
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
     *
     * @param array $param  原始URI参数
     * @param array $append 追加的参数
     *
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