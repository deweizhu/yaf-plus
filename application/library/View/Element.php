<?php

/**
 *  表单元素扩展类
 *
 * @author : Not well-known man
 *
 */
class View_Element
{
    /**
     * 在twig中使用的方法: View::Demo(...)
     *
     * @param array ...$args
     *
     * @return array
     */
    public static function Demo(...$args): array
    {
        if (func_num_args() === 1)
            $id = $args[0];
        else
            list($context, $id) = $args;
        $id += 0;
        if ($id <= 0)
            return [];
        //此处可实现调用Model查询数据库等方法，直接return数组即可
        return [];
    }
}