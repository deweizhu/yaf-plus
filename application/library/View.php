<?php

/**
 * view 帮助类
 *
 * @author    知名不具
 * @date      : 2015-11-26
 */
class View
{
    /**
     * 使用方法：
     * <?php echo View::asset('/asset/gmu/zepto.min.js'); ?>
     * 在twig下使用：{{ asset('/asset/gmu/zepto.min.js') }}
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
        //远程URL和min版、生产环境，直接返回
        if (strpos($uri, '://') !== FALSE || strpos($uri, '.min') !== FALSE || 'product' === Yaf_Application::app()->environ())
            return $uri;
        //开发环境下加随机数，防止浏览器缓存
        $file = PUBPATH . $uri;
        if (is_file($file))
            $uri .= '?_' . filemtime($file);
        elseif (is_file($file . '.js')) {
            //pass
        }
        else
            $uri = '';
        return $uri;
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

    /**
     * 获取当前请求查询字符串
     * twig用法：{{ query_string('date=all') }}
     * {{ query_string('date=all', 'id=123456') }}
     *
     * @param array ...$args 格式：array('k=v&k2=v2', ...) 或者 array('k=v', 'k2=v2', ...)
     *
     * @return string
     */
    public static function query_string(...$args): string
    {
        if (Arr::is_array($args[0]))
            $q = implode('&', array_shift($args));
        else
            $q = implode('&', $args);
        $append = array();
        parse_str($q, $append);
        $param = $append ? array_merge($_GET, $append) : $_GET;
        $uri = http_build_query(array_filter($param, function ($v){
                if ($v === 0 || $v === '0')
                    return TRUE;
                return (bool)$v;
        }), '', '&');
        return $uri;
    }
}