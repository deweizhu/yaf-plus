<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Array helper.
 *
 * @package    Elixir
 * @category   Helpers
 * @author    知名不具
 * @copyright  (c) 2007-2012 Elixir Team
 * @license
 */
class Arr {

	/**
	 * @var  string  default delimiter for path()
	 */
	public static $delimiter = '.';

	/**
	 * Tests if an array is associative or not.
	 *
	 *     // Returns TRUE
	 *     Arr::is_assoc(array('username' => 'john.doe'));
	 *
	 *     // Returns FALSE
	 *     Arr::is_assoc('foo', 'bar');
	 *
	 * @param   array   $array  array to check
	 * @return  boolean
	 */
	public static function is_assoc(array $array)
	{
		// Keys of the array
		$keys = array_keys($array);

		// If the array keys of the keys match the keys, then the array must
		// not be associative (e.g. the keys array looked like {0:0, 1:1...}).
		return array_keys($keys) !== $keys;
	}

	/**
	 * Test if a value is an array with an additional check for array-like objects.
	 *
	 *     // Returns TRUE
	 *     Arr::is_array(array());
	 *     Arr::is_array(new ArrayObject);
	 *
	 *     // Returns FALSE
	 *     Arr::is_array(FALSE);
	 *     Arr::is_array('not an array!');
	 *     Arr::is_array(Database::instance());
	 *
	 * @param   mixed   $value  value to check
	 * @return  boolean
	 */
	public static function is_array($value)
	{
		if (is_array($value))
		{
			// Definitely an array
			return TRUE;
		}
		else
		{
			// Possibly a Traversable object, functionally the same as an array
			return (is_object($value) AND $value instanceof Traversable);
		}
	}

	/**
	 * Gets a value from an array using a dot separated path.
	 *
	 *     // Get the value of $array['foo']['bar']
	 *     $value = Arr::path($array, 'foo.bar');
	 *
	 * Using a wildcard "*" will search intermediate arrays and return an array.
	 *
	 *     // Get the values of "color" in theme
	 *     $colors = Arr::path($array, 'theme.*.color');
	 *
	 *     // Using an array of keys
	 *     $colors = Arr::path($array, array('theme', '*', 'color'));
	 *
	 * @param   array   $array      array to search
	 * @param   mixed   $path       key path string (delimiter separated) or array of keys
	 * @param   mixed   $default    default value if the path is not set
	 * @param   string  $delimiter  key path delimiter
	 * @return  mixed
	 */
	public static function path($array, $path, $default = NULL, $delimiter = NULL)
	{
		if ( ! Arr::is_array($array))
		{
			// This is not an array!
			return $default;
		}

		if (is_array($path))
		{
			// The path has already been separated into keys
			$keys = $path;
		}
		else
		{
			if (array_key_exists($path, $array))
			{
				// No need to do extra processing
				return $array[$path];
			}

			if ($delimiter === NULL)
			{
				// Use the default delimiter
				$delimiter = Arr::$delimiter;
			}

			// Remove starting delimiters and spaces
			$path = ltrim($path, "{$delimiter} ");

			// Remove ending delimiters, spaces, and wildcards
			$path = rtrim($path, "{$delimiter} *");

			// Split the keys by delimiter
			$keys = explode($delimiter, $path);
		}

		do
		{
			$key = array_shift($keys);

			if (ctype_digit($key))
			{
				// Make the key an integer
				$key = (int) $key;
			}

			if (isset($array[$key]))
			{
				if ($keys)
				{
					if (Arr::is_array($array[$key]))
					{
						// Dig down into the next part of the path
						$array = $array[$key];
					}
					else
					{
						// Unable to dig deeper
						break;
					}
				}
				else
				{
					// Found the path requested
					return $array[$key];
				}
			}
			elseif ($key === '*')
			{
				// Handle wildcards

				$values = array();
				foreach ($array as $arr)
				{
					if ($value = Arr::path($arr, implode('.', $keys)))
					{
						$values[] = $value;
					}
				}

				if ($values)
				{
					// Found the values requested
					return $values;
				}
				else
				{
					// Unable to dig deeper
					break;
				}
			}
			else
			{
				// Unable to dig deeper
				break;
			}
		}
		while ($keys);

		// Unable to find the value requested
		return $default;
	}

	/**
	* Set a value on an array by path.
	*
	* @see Arr::path()
	* @param array   $array     Array to update
	* @param string  $path      Path
	* @param mixed   $value     Value to set
	* @param string  $delimiter Path delimiter
	*/
	public static function set_path( & $array, $path, $value, $delimiter = NULL)
	{
		if ( ! $delimiter)
		{
			// Use the default delimiter
			$delimiter = Arr::$delimiter;
		}

		// The path has already been separated into keys
		$keys = $path;
		if ( ! is_array($path))
		{
			// Split the keys by delimiter
			$keys = explode($delimiter, $path);
		}

		// Set current $array to inner-most array path
		while (count($keys) > 1)
		{
			$key = array_shift($keys);

			if (ctype_digit($key))
			{
				// Make the key an integer
				$key = (int) $key;
			}

			if ( ! isset($array[$key]))
			{
				$array[$key] = array();
			}

			$array = & $array[$key];
		}

		// Set key on inner-most array
		$array[array_shift($keys)] = $value;
	}

	/**
	 * Fill an array with a range of numbers.
	 *
	 *     // Fill an array with values 5, 10, 15, 20
	 *     $values = Arr::range(5, 20);
	 *
	 * @param   integer $step   stepping
	 * @param   integer $max    ending number
	 * @return  array
	 */
	public static function range($step = 10, $max = 100)
	{
		if ($step < 1)
			return array();

		$array = array();
		for ($i = $step; $i <= $max; $i += $step)
		{
			$array[$i] = $i;
		}

		return $array;
	}

	/**
	 * Retrieve a single key from an array. If the key does not exist in the
	 * array, the default value will be returned instead.
	 *
	 *     // Get the value "username" from $_POST, if it exists
	 *     $username = Arr::get($_POST, 'username');
	 *
	 *     // Get the value "sorting" from $_GET, if it exists
	 *     $sorting = Arr::get($_GET, 'sorting');
	 *
	 * @param   array   $array      array to extract from
	 * @param   string  $key        key name
	 * @param   mixed   $default    default value
	 * @return  mixed
	 */
	public static function get($array, $key, $default = NULL)
	{
		if ($array instanceof ArrayObject) {
			// This is a workaround for inconsistent implementation of isset between PHP and HHVM
			// See https://github.com/facebook/hhvm/issues/3437
			return $array->offsetExists($key) ? $array->offsetGet($key) : $default;
		} else {
			return isset($array[$key]) ? $array[$key] : $default;
		}
	}

	/**
	 * Retrieves multiple paths from an array. If the path does not exist in the
	 * array, the default value will be added instead.
	 *
	 *     // Get the values "username", "password" from $_POST
	 *     $auth = Arr::extract($_POST, array('username', 'password'));
	 *
	 *     // Get the value "level1.level2a" from $data
	 *     $data = array('level1' => array('level2a' => 'value 1', 'level2b' => 'value 2'));
	 *     Arr::extract($data, array('level1.level2a', 'password'));
	 *
	 * @param   array  $array    array to extract paths from
	 * @param   array  $paths    list of path
	 * @param   mixed  $default  default value
	 * @return  array
	 */
	public static function extract($array, array $paths, $default = NULL)
	{
		$found = array();
		foreach ($paths as $path)
		{
			Arr::set_path($found, $path, Arr::path($array, $path, $default));
		}

		return $found;
	}

	/**
	 * Retrieves muliple single-key values from a list of arrays.
	 *
	 *     // Get all of the "id" values from a result
	 *     $ids = Arr::pluck($result, 'id');
	 *
	 * [!!] A list of arrays is an array that contains arrays, eg: array(array $a, array $b, array $c, ...)
	 *
	 * @param   array   $array  list of arrays to check
	 * @param   string  $key    key to pluck
	 * @return  array
	 */
	public static function pluck($array, $key)
	{
		$values = array();

		foreach ($array as $row)
		{
			if (isset($row[$key]))
			{
				// Found a value in this row
				$values[] = $row[$key];
			}
		}

		return $values;
	}

	/**
	 * Adds a value to the beginning of an associative array.
	 *
	 *     // Add an empty value to the start of a select list
	 *     Arr::unshift($array, 'none', 'Select a value');
	 *
	 * @param   array   $array  array to modify
	 * @param   string  $key    array key name
	 * @param   mixed   $val    array value
	 * @return  array
	 */
	public static function unshift( array & $array, $key, $val)
	{
		$array = array_reverse($array, TRUE);
		$array[$key] = $val;
		$array = array_reverse($array, TRUE);

		return $array;
	}

	/**
	 * Recursive version of [array_map](http://php.net/array_map), applies one or more
	 * callbacks to all elements in an array, including sub-arrays.
	 *
	 *     // Apply "strip_tags" to every element in the array
	 *     $array = Arr::map('strip_tags', $array);
	 *
	 *     // Apply $this->filter to every element in the array
	 *     $array = Arr::map(array(array($this,'filter')), $array);
	 *
	 *     // Apply strip_tags and $this->filter to every element
	 *     $array = Arr::map(array('strip_tags',array($this,'filter')), $array);
	 *
	 * [!!] Because you can pass an array of callbacks, if you wish to use an array-form callback
	 * you must nest it in an additional array as above. Calling Arr::map(array($this,'filter'), $array)
	 * will cause an error.
	 * [!!] Unlike `array_map`, this method requires a callback and will only map
	 * a single array.
	 *
	 * @param   mixed   $callbacks  array of callbacks to apply to every element in the array
	 * @param   array   $array      array to map
	 * @param   array   $keys       array of keys to apply to
	 * @return  array
	 */
	public static function map($callbacks, $array, $keys = NULL)
	{
		foreach ($array as $key => $val)
		{
			if (is_array($val))
			{
				$array[$key] = Arr::map($callbacks, $array[$key]);
			}
			elseif ( ! is_array($keys) OR in_array($key, $keys))
			{
				if (is_array($callbacks))
				{
					foreach ($callbacks as $cb)
					{
						$array[$key] = call_user_func($cb, $array[$key]);
					}
				}
				else
				{
					$array[$key] = call_user_func($callbacks, $array[$key]);
				}
			}
		}

		return $array;
	}

	/**
	 * Recursively merge two or more arrays. Values in an associative array
	 * overwrite previous values with the same key. Values in an indexed array
	 * are appended, but only when they do not already exist in the result.
	 *
	 * Note that this does not work the same as [array_merge_recursive](http://php.net/array_merge_recursive)!
	 *
	 *     $john = array('name' => 'john', 'children' => array('fred', 'paul', 'sally', 'jane'));
	 *     $mary = array('name' => 'mary', 'children' => array('jane'));
	 *
	 *     // John and Mary are married, merge them together
	 *     $john = Arr::merge($john, $mary);
	 *
	 *     // The output of $john will now be:
	 *     array('name' => 'mary', 'children' => array('fred', 'paul', 'sally', 'jane'))
	 *
	 * @param   array  $array1      initial array
	 * @param   array  $array2,...  array to merge
	 * @return  array
	 */
	public static function merge($array1, $array2)
	{
		if (Arr::is_assoc($array2))
		{
			foreach ($array2 as $key => $value)
			{
				if (is_array($value)
					AND isset($array1[$key])
					AND is_array($array1[$key])
				)
				{
					$array1[$key] = Arr::merge($array1[$key], $value);
				}
				else
				{
					$array1[$key] = $value;
				}
			}
		}
		else
		{
			foreach ($array2 as $value)
			{
				if ( ! in_array($value, $array1, TRUE))
				{
					$array1[] = $value;
				}
			}
		}

		if (func_num_args() > 2)
		{
			foreach (array_slice(func_get_args(), 2) as $array2)
			{
				if (Arr::is_assoc($array2))
				{
					foreach ($array2 as $key => $value)
					{
						if (is_array($value)
							AND isset($array1[$key])
							AND is_array($array1[$key])
						)
						{
							$array1[$key] = Arr::merge($array1[$key], $value);
						}
						else
						{
							$array1[$key] = $value;
						}
					}
				}
				else
				{
					foreach ($array2 as $value)
					{
						if ( ! in_array($value, $array1, TRUE))
						{
							$array1[] = $value;
						}
					}
				}
			}
		}

		return $array1;
	}

	/**
	 * Overwrites an array with values from input arrays.
	 * Keys that do not exist in the first array will not be added!
	 *
	 *     $a1 = array('name' => 'john', 'mood' => 'happy', 'food' => 'bacon');
	 *     $a2 = array('name' => 'jack', 'food' => 'tacos', 'drink' => 'beer');
	 *
	 *     // Overwrite the values of $a1 with $a2
	 *     $array = Arr::overwrite($a1, $a2);
	 *
	 *     // The output of $array will now be:
	 *     array('name' => 'jack', 'mood' => 'happy', 'food' => 'tacos')
	 *
	 * @param   array   $array1 master array
	 * @param   array   $array2 input arrays that will overwrite existing values
	 * @return  array
	 */
	public static function overwrite($array1, $array2)
	{
		foreach (array_intersect_key($array2, $array1) as $key => $value)
		{
			$array1[$key] = $value;
		}

		if (func_num_args() > 2)
		{
			foreach (array_slice(func_get_args(), 2) as $array2)
			{
				foreach (array_intersect_key($array2, $array1) as $key => $value)
				{
					$array1[$key] = $value;
				}
			}
		}

		return $array1;
	}

	/**
	 * Creates a callable function and parameter list from a string representation.
	 * Note that this function does not validate the callback string.
	 *
	 *     // Get the callback function and parameters
	 *     list($func, $params) = Arr::callback('Foo::bar(apple,orange)');
	 *
	 *     // Get the result of the callback
	 *     $result = call_user_func_array($func, $params);
	 *
	 * @param   string  $str    callback string
	 * @return  array   function, params
	 */
	public static function callback($str)
	{
		// Overloaded as parts are found
		$command = $params = NULL;

		// command[param,param]
		if (preg_match('/^([^\(]*+)\((.*)\)$/', $str, $match))
		{
			// command
			$command = $match[1];

			if ($match[2] !== '')
			{
				// param,param
				$params = preg_split('/(?<!\\\\),/', $match[2]);
				$params = str_replace('\,', ',', $params);
			}
		}
		else
		{
			// command
			$command = $str;
		}

		if (strpos($command, '::') !== FALSE)
		{
			// Create a static method callable command
			$command = explode('::', $command, 2);
		}

		return array($command, $params);
	}

	/**
	 * Convert a multi-dimensional array into a single-dimensional array.
	 *
	 *     $array = array('set' => array('one' => 'something'), 'two' => 'other');
	 *
	 *     // Flatten the array
	 *     $array = Arr::flatten($array);
	 *
	 *     // The array will now be
	 *     array('one' => 'something', 'two' => 'other');
	 *
	 * [!!] The keys of array values will be discarded.
	 *
	 * @param   array   $array  array to flatten
	 * @return  array
	 * @since   3.0.6
	 */
	public static function flatten($array)
	{
		$is_assoc = Arr::is_assoc($array);

		$flat = array();
		foreach ($array as $key => $value)
		{
			if (is_array($value))
			{
				$flat = array_merge($flat, Arr::flatten($value));
			}
			else
			{
				if ($is_assoc)
				{
					$flat[$key] = $value;
				}
				else
				{
					$flat[] = $value;
				}
			}
		}
		return $flat;
	}

	/**
	 * 函数向数组中添加一个键-值对（如果给定的键不存在）
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	public static function add($array, $key, $value)
	{
	    if (is_null(static::get($array, $key))) {
	        static::set($array, $key, $value);
	    }
	
	    return $array;
	}
	
	/**
	 * Build a new array using a callback.
	 *
	 * @param  array  $array
	 * @param  callable  $callback
	 * @return array
	 */
	public static function build(array $array, callable $callback):array
	{
	    $results = [];
	
	    foreach ($array as $key => $value) {
	        list($innerKey, $innerValue) = call_user_func($callback, $key, $value);
	
	        $results[$innerKey] = $innerValue;
	    }
	
	    return $results;
	}
	
	/**
	 * 多维数组转换成一位数组
	 *
	 * @param  array  $array
	 * @return array
	 */
	public static function collapse(array $array):array
	{
	    $results = [];
	
	    foreach ($array as $values) {
	        $results = array_merge($results, $values);
	    }
	
	    return $results;
	}
	
	/**
	 * 返回两个数组，一个包含原数组的所有键，另一个包含原数组的所有值：
	 *
	 * @param  array  $array
	 * @return array
	 */
	public static function divide(array $array):array
	{
	    return [array_keys($array), array_values($array)];
	}
	
	/**
	 * 函数将一个多维数组转换为一维数组，并使用点号指示深度
	 * 例：$array = array_dot(['foo' => ['bar' => 'baz']]); // ['foo.bar' => 'baz'];
	 *
	 * @param  array   $array
	 * @param  string  $prepend
	 * @return array
	 */
	public static function dot($array, $prepend = '')
	{
	    $results = [];
	
	    foreach ($array as $key => $value) {
	        if (is_array($value)) {
	            $results = array_merge($results, static::dot($value, $prepend.$key.'.'));
	        } else {
	            $results[$prepend.$key] = $value;
	        }
	    }
	
	    return $results;
	}
	
	/**
	 * 方法从一个数组中移除指定的键/值对：
	 *
	 * @param  array  $array
	 * @param  array|string  $keys
	 * @return array
	 */
	public static function except(array $array, $keys):array
	{
	    static::forget($array, $keys);
	
	    return $array;
	}
	
	/**
	 * Fetch a flattened array of a nested array element.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return array
	 *
	 * @deprecated since version 5.1. Use pluck instead.
	 */
	public static function fetch($array, $key)
	{
	    foreach (explode('.', $key) as $segment) {
	        $results = [];
	
	        foreach ($array as $value) {
	            if (array_key_exists($segment, $value = (array) $value)) {
	                $results[] = $value[$segment];
	            }
	        }
	
	        $array = array_values($results);
	    }
	
	    return array_values($results);
	}
	
	/**
	 * 方法返回数组中第一个通过判断返回为真的元素：
	 *
	 * @param  array  $array
	 * @param  callable  $callback
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function first($array, callable $callback, $default = null)
	{
	    foreach ($array as $key => $value) {
	        if (call_user_func($callback, $key, $value)) {
	            return $value;
	        }
	    }
	
	    return value($default);
	}
	
	/**
	 * 法返回数组中最后一个通过判断返回为真的元素
	 *
	 * @param  array  $array
	 * @param  callable  $callback
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function last($array, callable $callback, $default = null)
	{
	    return static::first(array_reverse($array), $callback, $default);
	}
	
	/**
	 *  方法基于点号路径从一个深度嵌套的数组中移除指定的键/值对：
	 *  
	 * @param  array  $array
	 * @param  array|string  $keys
	 * @return void
	 */
	public static function forget(&$array, $keys)
	{
	    $original = &$array;
	
	    $keys = (array) $keys;
	
	    if (count($keys) === 0) {
	        return;
	    }
	
	    foreach ($keys as $key) {
	        $parts = explode('.', $key);
	
	        while (count($parts) > 1) {
	            $part = array_shift($parts);
	
	            if (isset($array[$part]) && is_array($array[$part])) {
	                $array = &$array[$part];
	            } else {
	                $parts = [];
	            }
	        }
	
	        unset($array[array_shift($parts)]);
	
	        // clean up after each pass
	        $array = &$original;
	    }
	}
	
	/**
	 * 数组包含
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return bool
	 */
	public static function has(array $array, string $key):bool
	{
	    if (empty($array) || is_null($key)) {
	        return false;
	    }
	
	    if (array_key_exists($key, $array)) {
	        return true;
	    }
	
	    foreach (explode('.', $key) as $segment) {
	        if (! is_array($array) || ! array_key_exists($segment, $array)) {
	            return false;
	        }
	
	        $array = $array[$segment];
	    }
	
	    return true;
	}
	
	/**
	 * Get a subset of the items from the given array.
	 *
	 * @param  array  $array
	 * @param  array|string  $keys
	 * @return array
	 */
	public static function only($array, $keys)
	{
	    return array_intersect_key($array, array_flip((array) $keys));
	}
	
	/**
	 * Explode the "value" and "key" arguments passed to "pluck".
	 *
	 * @param  string|array  $value
	 * @param  string|array|null  $key
	 * @return array
	 */
	protected static function explodePluckParameters($value, $key)
	{
	    $value = is_string($value) ? explode('.', $value) : $value;
	
	    $key = is_null($key) || is_array($key) ? $key : explode('.', $key);
	
	    return [$value, $key];
	}
	
	/**
	 * Push an item onto the beginning of an array.
	 *
	 * @param  array  $array
	 * @param  mixed  $value
	 * @param  mixed  $key
	 * @return array
	 */
	public static function prepend($array, $value, $key = null)
	{
	    if (is_null($key)) {
	        array_unshift($array, $value);
	    } else {
	        $array = [$key => $value] + $array;
	    }
	
	    return $array;
	}
	
	/**
	 * Get a value from the array, and remove it.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function pull(&$array, $key, $default = null)
	{
	    $value = static::get($array, $key, $default);
	
	    static::forget($array, $key);
	
	    return $value;
	}
	
	/**
	 * Set an array item to a given value using "dot" notation.
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	public static function set(&$array, $key, $value)
	{
	    if (is_null($key)) {
	        return $array = $value;
	    }
	
	    $keys = explode('.', $key);
	
	    while (count($keys) > 1) {
	        $key = array_shift($keys);
	
	        // If the key doesn't exist at this depth, we will just create an empty array
	        // to hold the next value, allowing us to create the arrays to hold final
	        // values at the correct depth. Then we'll keep digging into the array.
	        if (! isset($array[$key]) || ! is_array($array[$key])) {
	            $array[$key] = [];
	        }
	
	        $array = &$array[$key];
	    }
	
	    $array[array_shift($keys)] = $value;
	
	    return $array;
	}
	
	/**
	 * Recursively sort an array by keys and values.
	 *
	 * @param  array  $array
	 * @return array
	 */
	public static function sortRecursive($array)
	{
	    foreach ($array as &$value) {
	        if (is_array($value)) {
	            $value = static::sortRecursive($value);
	        }
	    }
	
	    if (static::isAssoc($array)) {
	        ksort($array);
	    } else {
	        sort($array);
	    }
	
	    return $array;
	}
	
	/**
	 * Filter the array using the given callback.
	 *
	 * @param  array  $array
	 * @param  callable  $callback
	 * @return array
	 */
	public static function where($array, callable $callback)
	{
	    $filtered = [];
	
	    foreach ($array as $key => $value) {
	        if (call_user_func($callback, $key, $value)) {
	            $filtered[$key] = $value;
	        }
	    }
	
	    return $filtered;
	}
}
