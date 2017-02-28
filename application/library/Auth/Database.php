<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * File Auth driver.
 * [!!] this Auth driver does not support roles nor autologin.
 *
 * @package    Elixir/Auth
 * @author    知名不具
 * @copyright  (c) 2007-2012 Elixir Team
 * @license
 */
class Auth_Database extends Elixir_Auth {

	// User list
	protected $_users;

	/**
	 * Constructor loads the user list into the class.
	 */
	public function __construct($config = array(),$name)
	{
		parent::__construct($config,$name);
	}

	/**
	 * Logs a user in.
	 *
	 * @param   string   $username  Username
	 * @param   string   $password  Password
	 * @param   boolean  $remember  Enable autologin (not supported)
	 * @return  boolean
	 */
	protected function _login(array $credentials, bool $remember):bool
	{
		$model = new $this->_config['model'];

        $user = $model->getUser($credentials);

		if(isset($user) && $remember === TRUE) {
		    array_forget($user, 'password');
		    return $this->complete_login($user);
		}
        if(!empty($user) && bcrypt_check($credentials['password'], $user->password)){
		    unset($user->password);
		    return $this->complete_login($user);
		}

		// Login failed
		return FALSE;
	}

	/**
	 * Forces a user to be logged in, without specifying a password.
	 *
	 * @param   mixed    $username  Username
	 * @return  boolean
	 */
	public function force_login($username)
	{
		// Complete the login
		return $this->complete_login($username);
	}

	/**
	 * Get the stored password for a username.
	 *
	 * @param   mixed   $username  Username
	 * @return  string
	 */
	public function password($user):string
	{
		return $user->password;
	}

	/**
	 * Compare password with original (plain text). Works for current (logged in) user
	 *
	 * @param   string   $password  Password
	 * @return  boolean
	 */
	public function check_password(string $password)
	{
		$user = $this->get();

		if ($user === FALSE)
		{
			return FALSE;
		}

		return ($password === $this->password($user));
	}

} // End Auth File
