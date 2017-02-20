<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Elixir Cache Arithmetic Interface, for basic cache integer based
 * arithmetic, addition and subtraction
 * 
 * @package    Elixir/Cache
 * @category   Base
 * @author    知名不具
 * @copyright  (c) 2009-2012 Elixir Team
 * @license
 * @since      3.2.0
 */
interface Cache_Arithmetic {

	/**
	 * Increments a given value by the step value supplied.
	 * Useful for shared counters and other persistent integer based
	 * tracking.
	 *
	 * @param   string    id of cache entry to increment
	 * @param   int       step value to increment by
	 * @return  integer
	 * @return  boolean
	 */
	public function increment($id, $step = 1);

	/**
	 * Decrements a given value by the step value supplied.
	 * Useful for shared counters and other persistent integer based
	 * tracking.
	 *
	 * @param   string    id of cache entry to decrement
	 * @param   int       step value to decrement by
	 * @return  integer
	 * @return  boolean
	 */
	public function decrement($id, $step = 1);

} // End Cache_Arithmetic