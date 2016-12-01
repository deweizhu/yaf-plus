<?php defined('SYSPATH') or die('No direct script access.');
/**
 * See [Cache_Memcache]
 *
* @package    Elixir/Cache
* @category   Base
* @version    2.0
* @author     Elixir Team
* @copyright  (c) 2009-2012 Elixir Team
* @license    http://Elixirphp.com/license
 */
class Cache_MemcacheTag extends Cache_Memcache implements Cache_Tagging {

	/**
	 * Constructs the memcache object
	 *
	 * @param  array  $config  configuration
	 * @throws  Elixir_Exception
	 */
	protected function __construct(array $config)
	{
		parent::__construct($config);

		if ( ! method_exists($this->_memcache, 'tag_add'))
		{
			throw new Elixir_Exception('Memcached-tags PHP plugin not present. Please see http://code.google.com/p/memcached-tags/ for more information');
		}
	}

	/**
	 * Set a value based on an id with tags
	 *
	 * @param   string   $id        id
	 * @param   mixed    $data      data
	 * @param   integer  $lifetime  lifetime [Optional]
	 * @param   array    $tags      tags [Optional]
	 * @return  boolean
	 */
	public function set_with_tags($id, $data, $lifetime = NULL, array $tags = NULL)
	{
		$id = $this->_sanitize_id($id);

		$result = $this->set($id, $data, $lifetime);

		if ($result and $tags)
		{
			foreach ($tags as $tag)
			{
				$this->_memcache->tag_add($tag, $id);
			}
		}

		return $result;
	}

	/**
	 * Delete cache entries based on a tag
	 *
	 * @param   string  $tag  tag
	 * @return  boolean
	 */
	public function delete_tag($tag)
	{
		return $this->_memcache->tag_delete($tag);
	}

	/**
	 * Find cache entries based on a tag
	 *
	 * @param   string  $tag  tag
	 * @return  void
	 * @throws  Elixir_Exception
	 */
	public function find($tag)
	{
		throw new Elixir_Exception('Memcached-tags does not support finding by tag');
	}
}
