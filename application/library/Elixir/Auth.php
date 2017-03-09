<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * User authorization library. Handles user login and logout, as well as secure
 * password hashing.
 *
 * @package    Elixir/Auth
 * @author    知名不具
 * @copyright  (c) 2007-2012 Elixir Team
 * @license
 */
abstract class Elixir_Auth {

	protected $_session;

	protected $_config;

	protected $name;
	
	protected $_cache;
	/**
	 * Loads Session and configuration options.
	 *
	 * @param   array  $config  Config Options
	 * @return  void
	 */
	public function __construct($config = array(),$name)
	{
		// Save the config in the object
		$this->_config = $config;
		
        $this->name = $name;		

		$this->_session = Session::instance($this->_config['session_type']);
		
		$this->_cache = Cache::instance();
	}

	abstract protected function _login(array $credentials, bool $remember);

	abstract public function password($credentials);

	abstract public function check_password(string $password);

	/**
	 * Gets the currently logged in user from the session.
	 * Returns NULL if no user is currently logged in.
	 *
	 * @param   mixed  $default  Default value to return if the user is currently not logged in.
	 * @return  mixed
	 */
	public function get($default = NULL)
	{
		return $this->_session->get($this->getName(), $default);
	}

	/**
	 * Attempt to log in a user by using an ORM object and plain-text password.
	 *
	 * @param   string   $username  Username to log in
	 * @param   string   $password  Password to check against
	 * @param   boolean  $remember  Enable autologin
	 * @return  boolean
	 */
	public function login(array $credentials, bool $remember = FALSE)
	{
		if (!array_has($credentials, 'password'))
			return FALSE;

		return $this->_login($credentials, $remember);
	}

	/**
	 * Log out a user by removing the related session variables.
	 *
	 * @param   boolean  $destroy     Completely destroy the session
	 * @param   boolean  $logout_all  Remove all tokens for user
	 * @return  boolean
	 */
	public function logout($destroy = FALSE, $logout_all = FALSE)
	{
		if ($destroy === TRUE)
		{
			// Destroy the session completely
			$this->_session->destroy();
		}
		else
		{
			// Remove the user from the session
			$this->_session->delete($this->getName());

			// Regenerate session_id
			$this->_session->regenerate();
		}

		// Double check
		return ! $this->logged_in();
	}

	/**
	 * Check if there is an active session. Optionally allows checking for a
	 * specific role.
	 *
	 * @param   string  $role  role name
	 * @return  mixed
	 */
	public function logged_in($role = NULL)
	{
		return ($this->get() !== NULL);
	}

	/**
	 * Creates a hashed hmac password from a plaintext password. This
	 * method is deprecated, [Auth::hash] should be used instead.
	 *
	 * @deprecated
	 * @param  string  $password Plaintext password
	 */
	public function hash_password($password)
	{
		return $this->hash($password);
	}

	/**
	 * Perform a hmac hash, using the configured method.
	 *
	 * @param   string  $str  string to hash
	 * @return  string
	 */
	public function hash($str)
	{
		if ( ! $this->_config['hash_key'])
			throw new Elixir_Exception('A valid hash key must be set in your auth config.');

		return hash_hmac($this->_config['hash_method'], $str, $this->_config['hash_key']);
	}

	protected function complete_login($user)
	{
		// Regenerate session_id
		$this->_session->regenerate();

		// Store username in session
		$this->_session->set($this->getName(), $user);

		return TRUE;
	}
	
	/**
	 * Get a unique identifier for the auth session value.
	 * @return string
	 */
	public function getName()
	{
	    return 'login_'.$this->name.'_'.md5(get_class($this));
	}

} // End Auth
