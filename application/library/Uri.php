<?php

/**
 * URI
 * @author    Not well-known man
 *
 *
 */
class Uri
{
    private static $scriptpath = '';

    /**
     * 获取 'scriptpath' 变量-即： 当前页的 URI
     *
     * @return    string
     */
    public static function fetchScriptpath()
    {
        if (self::$scriptpath != '') {
            return self::$scriptpath;
        } else {
            $scriptpath = self::fetchScriptpathRaw();
            // 将来我们应该在这儿设置 $registry->script
            $quest_pos = strpos($scriptpath, '?');
            if ($quest_pos !== FALSE) {
                $script = urldecode(substr($scriptpath, 0, $quest_pos));
                $scriptpath = $script . substr($scriptpath, $quest_pos);
            } else {
                $scriptpath = urldecode($scriptpath);
            }
            self::$scriptpath = $scriptpath;
            return $scriptpath;
        }
    }

    /**
     * Fetches the raw scriptpath.
     *
     * @return string
     */
    public static function fetchScriptpathRaw()
    {
        if ($_SERVER ['REQUEST_URI'] or $_ENV ['REQUEST_URI']) {
            $scriptpath = $_SERVER ['REQUEST_URI'] ? $_SERVER ['REQUEST_URI'] : $_ENV ['REQUEST_URI'];
        } else {
            if ($_SERVER ['PATH_INFO'] or $_ENV ['PATH_INFO']) {
                $scriptpath = $_SERVER ['PATH_INFO'] ? $_SERVER ['PATH_INFO'] : $_ENV ['PATH_INFO'];
            } else if ($_SERVER ['REDIRECT_URL'] or $_ENV ['REDIRECT_URL']) {
                $scriptpath = $_SERVER ['REDIRECT_URL'] ? $_SERVER ['REDIRECT_URL'] : $_ENV ['REDIRECT_URL'];
            } else {
                $scriptpath = $_SERVER ['PHP_SELF'] ? $_SERVER ['PHP_SELF'] : $_ENV ['PHP_SELF'];
            }

            if ($_SERVER ['QUERY_STRING'] or $_ENV ['QUERY_STRING']) {
                $scriptpath .= '?' . ($_SERVER ['QUERY_STRING'] ? $_SERVER ['QUERY_STRING'] : $_ENV ['QUERY_STRING']);
            }
        }
        return $scriptpath;
    }
}