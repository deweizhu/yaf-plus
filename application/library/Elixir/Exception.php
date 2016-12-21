<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Elixir exception class. Translates exceptions using the [I18n] class.
 *
 * @package    Elixir
 * @category   Exceptions
 * @author    知名不具
 * @copyright  (c) 2016-2017 Elixir Team
 * @license
 */
class Elixir_Exception extends Exception {

	/**
	 * @var  array  PHP error code => human readable name
	 */
	public static $php_errors = array(
		E_ERROR              => 'Fatal Error',
		E_USER_ERROR         => 'User Error',
		E_PARSE              => 'Parse Error',
		E_WARNING            => 'Warning',
		E_USER_WARNING       => 'User Warning',
		E_STRICT             => 'Strict',
		E_NOTICE             => 'Notice',
		E_RECOVERABLE_ERROR  => 'Recoverable Error',
		E_DEPRECATED         => 'Deprecated',
	);

	/**
	 * @var  string  error rendering view
	 */
	public static $error_view = 'Elixir/Error.php';

	/**
	 * @var  string  error view content type
	 */
	public static $error_view_content_type = 'text/html';

    /**
     * @var  Log  logging object
     */
    public static $log;

	/**
	 * Creates a new translated exception.
	 *
	 *     throw new Elixir_Exception('Something went terrible wrong, :user',
	 *         array(':user' => $user));
	 *
	 * @param   string          $message    error message
	 * @param   array           $variables  translation variables
	 * @param   integer|string  $code       the exception code
	 * @param   Exception       $previous   Previous exception
	 * @return  void
	 */
	public function __construct($message = "", array $variables = NULL, $code = 0, Exception $previous = NULL)
	{
        // Load the logger if one doesn't already exist
        if (!self::$log instanceof Log)
        {
            self::$log = Log::instance();
        }
		// Set the message
        if ($variables) {
            foreach ($variables as $key => $val) {
                $message = str_replace($key, $val, $message);
            }
        }
		// Pass the message and integer code to the parent
		parent::__construct($message, (int) $code, $previous);

		// Save the unmodified code
		// @link http://bugs.php.net/39615
		$this->code = $code;
	}

	/**
	 * Magic object-to-string method.
	 *
	 *     echo $exception;
	 *
	 * @uses    Elixir_Exception::text
	 * @return  string
	 */
	public function __toString()
	{
		return Elixir_Exception::text($this);
	}

	/**
	 * Inline exception handler, displays the error message, source of the
	 * exception, and the stack trace of the error.
	 *
	 * @uses    Elixir_Exception::response
	 * @param   Exception  $e
	 * @return  void
	 */
	public static function handler(Exception $e)
	{
		$response = Elixir_Exception::_handler($e);
        if ('product' !== Yaf_Application::app()->environ()) {
            // Send the response to the browser
            echo $response;
        }
		exit(1);
	}

	/**
	 * Exception handler, logs the exception and generates a Response object
	 * for display.
	 *
	 * @uses    Elixir_Exception::response
	 * @param   Exception  $e
	 * @return  Response
	 */
	public static function _handler(Exception $e)
	{
		try
		{
			// Log the exception
			Elixir_Exception::log($e);

			// Generate the response
			$response = Elixir_Exception::response($e);

			return $response;
		}
		catch (Exception $e)
		{
			/**
			 * Things are going *really* badly for us, We now have no choice
			 * but to bail. Hard.
			 */
			// Clean the output buffer if one exists
			ob_get_level() AND ob_clean();

			// Set the Status code to 500, and Content-Type to text/plain.
			header('Content-Type: text/plain; charset=utf-8', TRUE, 500);

			echo Elixir_Exception::text($e);

			exit(1);
		}
	}

	/**
	 * Logs an exception.
	 *
	 * @uses    Elixir_Exception::text
	 * @param   Exception  $e
	 * @param   int        $level
	 * @return  void
	 */
	public static function log(Exception $e, $level = Log::EMERGENCY)
	{
        // self::$log = Log::instance();
		if (is_object(self::$log))
		{
			// Create a text version of the exception
			$error = Elixir_Exception::text($e);
			// Add this exception to the log
			self::$log->add($level, $error, NULL, array('exception' => $e));
			// Make sure the logs are written
			self::$log->write();
		}
	}

	/**
	 * Get a single line of text representing the exception:
	 *
	 * Error [ Code ]: Message ~ File [ Line ]
	 *
	 * @param   Exception  $e
	 * @return  string
	 */
	public static function text(Exception $e)
	{
		return sprintf('%s [ %s ]: %s ~ %s [ %d ]',
			get_class($e), $e->getCode(), strip_tags($e->getMessage()), Debug::path($e->getFile()), $e->getLine());
	}

	/**
	 * Get a Response object representing the exception
	 *
	 * @uses    Elixir_Exception::text
	 * @param   Exception  $e
	 * @return  Response
	 */
	public static function response(Exception $e)
	{
		try
		{
			// Get the exception information
			$class   = get_class($e);
			$code    = $e->getCode();
			$message = $e->getMessage();
			$file    = $e->getFile();
			$line    = $e->getLine();
			$trace   = $e->getTrace();


			if ($e instanceof ErrorException)
			{
				/**
				 * If XDebug is installed, and this is a fatal error,
				 * use XDebug to generate the stack trace
				 */
				if (function_exists('xdebug_get_function_stack') AND $code == E_ERROR)
				{
					$trace = array_slice(array_reverse(xdebug_get_function_stack()), 4);

					foreach ($trace as & $frame)
					{
						/**
						 * XDebug pre 2.1.1 doesn't currently set the call type key
						 * http://bugs.xdebug.org/view.php?id=695
						 */
						if ( ! isset($frame['type']))
						{
							$frame['type'] = '??';
						}

						// Xdebug returns the words 'dynamic' and 'static' instead of using '->' and '::' symbols
						if ('dynamic' === $frame['type'])
						{
							$frame['type'] = '->';
						}
						elseif ('static' === $frame['type'])
						{
							$frame['type'] = '::';
						}

						// XDebug also has a different name for the parameters array
						if (isset($frame['params']) AND ! isset($frame['args']))
						{
							$frame['args'] = $frame['params'];
						}
					}
				}

				if (isset(Elixir_Exception::$php_errors[$code]))
				{
					// Use the human-readable error name
					$code = Elixir_Exception::$php_errors[$code];
				}
			}

			/**
			 * The stack trace becomes unmanageable inside PHPUnit.
			 *
			 * The error view ends up several GB in size, taking
			 * serveral minutes to render.
			 */
			if (
				defined('PHPUnit_MAIN_METHOD')
				OR
				defined('PHPUNIT_COMPOSER_INSTALL')
				OR
				defined('__PHPUNIT_PHAR__')
			)
			{
				$trace = array_slice($trace, 0, 2);
			}
            // Instantiate the error view.
            $view = Elixir_View::factory(Elixir_Exception::$error_view, get_defined_vars());
            return $view->render();

		}
		catch (Exception $e)
		{
			/**
			 * Things are going badly for us, Lets try to keep things under control by
			 * generating a simpler response object.
			 */
            $response = Elixir_Exception::text($e);
		}

		return $response;
	}

}
