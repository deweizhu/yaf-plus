<?php
/**
 * @Not well-known man
 * 公共方法
 */

if (!function_exists('dateformat')) {
    /**
     * 时间格式处理
     * @param array $array
     * @param string $key
     */
    function dateformat(&$array, $key = 'created_at')
    {
        if (array_has($array, $key)) {
//            $array['created_at'] = Date::time_format($array[$key]);
            $array['created_at'] = date('Y-m-d H:i:s',$array[$key]);
        }
    }
}

if (!function_exists('create_coupon_no')) {
    /**
     * 返回一个代金券的券号
     * @return string
     */
    function create_coupon_no()
    {
        $cache = Cache::instance();
        $coupon_no = strtolower(date('Ymdhis', time()) . Text::random('numeric', 4));

        if (!empty($cache->getRedis()->hget(crc32('coupons_no'), $coupon_no))) {
            $coupon_no = create_coupon_no();
        } else {
            $cache->getRedis()->hset(crc32('coupons_no'), $coupon_no, $coupon_no);
        }
        return $coupon_no;
    }
}

if (!function_exists('abort')) {
    /**
     * 跳出
     *
     * @param int $code
     * @param string $message
     * @param array $headers
     */
    function abort(int $code, string $message = '', array $headers = [])
    {
        return Response::abort($code, $message, $headers);
    }
}

if (!function_exists('bcrypt')) {
    /**
     * 给定值hash.
     *
     * @param string $value
     * @param array $options
     * @return string
     */
    function bcrypt($value, $options = [])
    {
        return BcryptHasher::getInstance()->make($value, $options);
    }
}

if (!function_exists('bcrypt_check')) {
    /**
     *
     * @param string $value
     * @param array $options
     * @return string
     */
    function bcrypt_check($value, $hashVlaue, $options = [])
    {
        return BcryptHasher::getInstance()->check($value, $hashVlaue, $options);
    }
}

if (!function_exists('check_password')) {
    /**
     *
     * @param string $value
     * @param array $options
     * @return string
     */
    function check_password($password, $inputpwd, $salt, $isMd5 = FALSE)
    {
        if (strlen($inputpwd) == 32) {
            $isMd5 = TRUE;
        }
        $md5pass = ($isMd5 ? md5($inputpwd . $salt) : md5(md5($inputpwd) . $salt));
        $ret = FALSE;
        if ($password === $md5pass) {
            $ret = TRUE;
        }
        return $ret;
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('camel_case')) {
    /**
     * 函数将给定字串转换为驼峰式
     * 例： camel_case('foo_bar'); // fooBar
     *
     * @param string $value
     * @return string
     */
    function camel_case($value)
    {
        return Text::camel($value);
    }
}

if (!function_exists('ends_with')) {
    /**
     * 函数判断字串是否以给定值结尾
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    function ends_with($haystack, $needles)
    {
        return Text::endsWith($haystack, $needles);
    }
}

if (!function_exists('head')) {
    /**
     * 函数简单返回给定数组的第一个元素：
     *
     * @param array $array
     * @return mixed
     */
    function head($array)
    {
        return reset($array);
    }
}

if (!function_exists('last')) {
    /**
     * 函数返回给定数组的最后一个元素：
     *
     * @param array $array
     * @return mixed
     */
    function last($array)
    {
        return end($array);
    }
}

if (!function_exists('snake_case')) {
    /**
     *  将给定字串转换为蛇形式
     * 例：snake_case('fooBar'); //foo_bar
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    function snake_case($value, $delimiter = '_')
    {
        return Text::snake($value, $delimiter);
    }
}

if (!function_exists('starts_with')) {
    /**
     * 函数判断字串是否以给定值开头：
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    function starts_with($haystack, $needles)
    {
        return Text::startsWith($haystack, $needles);
    }
}

if (!function_exists('str_contains')) {
    /**
     * 判断字串是否包含给定值：
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    function str_contains($haystack, $needles)
    {
        return Text::contains($haystack, $needles);
    }
}

if (!function_exists('str_finish')) {
    /**
     *  函数为字串添加给定单例
     * 例：$string = str_finish('this/string', '/'); // this/string/
     *
     * @param string $value
     * @param string $cap
     * @return string
     */
    function str_finish($value, $cap)
    {
        return Text::finish($value, $cap);
    }
}

if (!function_exists('str_is')) {
    /**
     * 函数判断字串是否匹配给定形式。星号表示通配符：
     * 例：$value = str_is('foo*', 'foobar');   //true
     *
     * @param string $pattern
     * @param string $value
     * @return bool
     */
    function str_is($pattern, $value)
    {
        return Text::is($pattern, $value);
    }
}

if (!function_exists('str_limit')) {
    /**
     * 函数限制一个字符串的长度。该函数接收一个字符串作为第一个参数，最大长度作为第二个参数：
     *
     * @param string $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    function str_limit($value, $limit = 100, $end = '...')
    {
        return Text::limit($value, $limit, $end);
    }
}

if (!function_exists('str_random')) {
    /**
     * 获取随机字符串
     *
     * @param int $length
     * @return string
     *
     * @throws \RuntimeException
     */
    function str_random($length = 16)
    {
        return Text::randomStr($length);
    }
}

if (!function_exists('str_slug')) {
    /**
     * 函数将字串转换为URL友好型
     * 例：str_slug("xweisoft media", "-");  //xweisoft-media
     *
     * @param string $title
     * @param string $separator
     * @return string
     */
    function str_slug($title, $separator = '-')
    {
        return Text::slug($title, $separator);
    }
}

if (!function_exists('studly_case')) {
    /**
     * 函数将字串转换为 StudlyCase 型
     * 例：studly('foo_bar'); // FooBar
     *
     * @param string $value
     * @return string
     */
    function studly_case($value)
    {
        return Text::studly($value);
    }
}

if (!function_exists('title_case')) {
    /**
     * Convert a value to title case.
     *
     * @param string $value
     * @return string
     */
    function title_case($value)
    {
        return Text::title($value);
    }
}

if (!function_exists('str_len')) {

    /**
     * 返回字符串长度
     * @param string $value
     * @return number
     */
    function str_len(string $value): int
    {
        return Text::length($value);
    }
}

if (!function_exists('array_add')) {
    /**
     * 函数向数组中添加一个键-值对（如果给定的键不存在）
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return array
     */
    function array_add($array, $key, $value)
    {
        return Arr::add($array, $key, $value);
    }
}

if (!function_exists('array_build')) {
    /**
     * Build a new array using a callback.
     *
     * @param array $array
     * @param callable $callback
     * @return array
     */
    function array_build($array, callable $callback)
    {
        return Arr::build($array, $callback);
    }
}

if (!function_exists('array_collapse')) {
    /**
     * 多维数组转换成一位数组
     *
     * @param array $array
     * @return array
     */
    function array_collapse($array)
    {
        return Arr::collapse($array);
    }
}

if (!function_exists('array_divide')) {
    /**
     * 返回两个数组，一个包含原数组的所有键，另一个包含原数组的所有值：
     *
     * @param array $array
     * @return array
     */
    function array_divide($array)
    {
        return Arr::divide($array);
    }
}

if (!function_exists('array_dot')) {
    /**
     *  函数将一个多维数组转换为一维数组，并使用点号指示深度
     * 例：$array = array_dot(['foo' => ['bar' => 'baz']]); // ['foo.bar' => 'baz'];
     *
     * @param array $array
     * @param string $prepend
     * @return array
     */
    function array_dot($array, $prepend = '')
    {
        return Arr::dot($array, $prepend);
    }
}

if (!function_exists('array_except')) {
    /**
     * 方法从一个数组中移除指定的键/值对：
     *
     * @param array $array
     * @param array|string $keys
     * @return array
     */
    function array_except($array, $keys)
    {
        return Arr::except($array, $keys);
    }
}

if (!function_exists('array_fetch')) {
    /**
     * Fetch a flattened array of a nested array element.
     *
     * @param array $array
     * @param string $key
     * @return array
     *
     * @deprecated since version 5.1. Use array_pluck instead.
     */
    function array_fetch($array, $key)
    {
        return Arr::fetch($array, $key);
    }
}

if (!function_exists('array_first')) {
    /**
     * 方法返回数组中第一个通过判断返回为真的元素：
     * 默认值可作为第三个参数传入。如果没有值通过判断，将返回默认值：
     *
     * @param array $array
     * @param callable $callback
     * @param mixed $default
     * @return mixed
     */
    function array_first($array, callable $callback, $default = NULL)
    {
        return Arr::first($array, $callback, $default);
    }
}

if (!function_exists('array_flatten')) {
    /**
     * 方法将一个多维数组转换为一维数组：
     *
     * @param array $array
     * @return array
     */
    function array_flatten($array)
    {
        return Arr::flatten($array);
    }
}

if (!function_exists('array_forget')) {
    /**
     * 方法基于点号路径从一个深度嵌套的数组中移除指定的键/值对：
     *
     * @param array $array
     * @param array|string $keys
     * @return void
     */
    function array_forget(&$array, $keys)
    {
        return Arr::forget($array, $keys);
    }
}

if (!function_exists('array_get')) {
    /**
     * 方法基于点号路径从一个深度嵌套的数组中取出值：
     * 方法也接受默认值，如果指定的键未找到，返回默认值：
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function array_get($array, $key, $default = NULL)
    {
        return Arr::get($array, $key, $default);
    }
}

if (!function_exists('array_has')) {
    /**
     * 数组包含：
     * $array = ['products' => ['desk' => ['price' => 100]]];
     *
     * $hasDesk = array_has($array, ['products.desk']); // true
     * @param array $array
     * @param string $key
     * @return bool
     */
    function array_has($array, $key): bool
    {
        return Arr::has($array, $key);
    }
}

if (!function_exists('array_last')) {
    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param array $array
     * @param callable $callback
     * @param mixed $default
     * @return mixed
     */
    function array_last($array, $callback, $default = NULL)
    {
        return Arr::last($array, $callback, $default);
    }
}

if (!function_exists('array_only')) {
    /**
     * 方法从给定的数组中返回指定的键/值对：
     * $array = ['name' => 'Desk', 'price' => 100, 'orders' => 10];
     *
     * $array = array_only($array, ['name', 'price']);
     *
     * // ['name' => 'Desk', 'price' => 100]
     * @param array $array
     * @param array|string $keys
     * @return array
     */
    function array_only($array, $keys)
    {
        return Arr::only($array, $keys);
    }
}

if (!function_exists('array_pluck')) {
    /**
     * 方法从给定的数组中提取出键/值对：
     * $array = [
     * ['developer' => ['id' => 1, 'name' => 'Taylor']],
     * ['developer' => ['id' => 2, 'name' => 'Abigail']],
     * ];
     *
     * $array = array_pluck($array, 'developer.name');
     *
     * // ['Taylor', 'Abigail'];
     * @param array $array
     * @param string|array $value
     * @param string|array|null $key
     * @return array
     */
    function array_pluck($array, $value, $key = NULL)
    {
        return Arr::pluck($array, $value, $key);
    }
}

if (!function_exists('array_prepend')) {
    /**
     * Push an item onto the beginning of an array.
     *
     * @param array $array
     * @param mixed $value
     * @param mixed $key
     * @return array
     */
    function array_prepend($array, $value, $key = NULL)
    {
        return Arr::prepend($array, $value, $key);
    }
}

if (!function_exists('array_pull')) {
    /**
     * 方法从数组中移除并返回一个键/值对：
     * $array = ['name' => 'Desk', 'price' => 100];
     *
     * $name = array_pull($array, 'name');
     *
     * // $name: Desk
     *
     * // $array: ['price' => 100]
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function array_pull(&$array, $key, $default = NULL)
    {
        return Arr::pull($array, $key, $default);
    }
}

if (!function_exists('array_set')) {
    /**
     * 方法基于点号路径为一个深度嵌套的数组设置值：
     * $array = ['products' => ['desk' => ['price' => 100]]];
     *
     * array_set($array, 'products.desk.price', 200);
     *
     * // ['products' => ['desk' => ['price' => 200]]]
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return array
     */
    function array_set(&$array, $key, $value)
    {
        return Arr::set($array, $key, $value);
    }
}

if (!function_exists('array_sort_recursive')) {
    /**
     * 方法用 sort 函数递归排序数组：
     *
     * @param array $array
     * @return array
     */
    function array_sort_recursive($array)
    {
        return Arr::sortRecursive($array);
    }
}

if (!function_exists('array_where')) {
    /**
     * 用给定闭包过滤数组：
     * $array = [100, '200', 300, '400', 500];
     *
     * $array = array_where($array, function ($key, $value) {
     * return is_string($value);
     * });
     *
     * // [1 => 200, 3 => 400]
     *
     * @param array $array
     * @param callable $callback
     * @return array
     */
    function array_where($array, callable $callback)
    {
        return Arr::where($array, $callback);
    }
}

if (!function_exists('user_permission')) {

    /**
     * 前端会员用户权限验证
     *
     * @param string|array $name
     * @param bool $requireAll
     * @return bool
     */
    function user_permission($name, bool $requireAll = FALSE): bool
    {
        $user = $_SESSION('user');

        $userModel = User_UsersModel::getInstance();

        $userModel->setId($user['id']);

        return $userModel->can($name, $requireAll);
    }
}


if (!function_exists('page_limit')) {
    /**
     * 分页大小
     * @return array
     */
    function page_limit()
    {
        $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $size = !empty($_REQUEST['size']) ? intval($_REQUEST['size']) :
            (Yaf\Application::app()->getConfig()->get('pagination.default.pagesize') ?? 10);
        return [$page, $size];
    }
}

if (!function_exists('invite_code')) {
    /**
     * user_id和邀请码相互转换
     * @param int $user_id 用户编号
     * @param bool $encode true返回邀请码，false返回user_id
     * @return float|int
     */
    function invite_code(int $user_id,bool $encode = true) :int
    {
        if($encode)
            $invite_code = $user_id * 12 + 8 * 2 + 99;
        else
            $invite_code = ($user_id - 99 - 8 * 2) / 12;
        return $invite_code;
    }
}