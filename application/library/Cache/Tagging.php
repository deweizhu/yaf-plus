<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Elixir Cache Tagging Interface
 *
 * @package    Elixir/Cache
 * @category   Base
 * @author     Elixir Team
 * @copyright  (c) 2009-2012 Elixir Team
 * @license    http://Elixirphp.com/license
 */
interface Cache_Tagging {

	/**
	 * Set a value based on an id. Optionally add tags.
	 *
	 * Note : Some caching engines do not support
	 * tagging
	 *
	 * @param   string   $id        id
	 * @param   mixed    $data      data
	 * @param   integer  $lifetime  lifetime [Optional]
	 * @param   array    $tags      tags [Optional]
	 * @return  boolean
	 */
	public function set_with_tags($id, $data, $lifetime = NULL, array $tags = NULL);

	/**
	 * Delete cache entries based on a tag
	 *
	 * @param   string  $tag  tag
	 */
	public function delete_tag($tag);

	/**
	 * Find cache entries based on a tag
	 *
	 * @param   string  $tag  tag
	 * @return  array
	 */
	public function find($tag);
}
