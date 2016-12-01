<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * STDOUT log writer. Writes out messages to STDOUT.
 *
 * @package    Elixir
 * @category   Logging
 * @author     Elixir Team
 * @copyright  (c) 2016-2017 Elixir Team
 * @license
 */
class Log_StdOut extends Log_Writer {

	/**
	 * Writes each of the messages to STDOUT.
	 *
	 *     $writer->write($messages);
	 *
	 * @param   array   $messages
	 * @return  void
	 */
	public function write(array $messages)
	{
		foreach ($messages as $message)
		{
			// Writes out each message
			fwrite(STDOUT, $this->format_message($message).PHP_EOL);
		}
	}

}
