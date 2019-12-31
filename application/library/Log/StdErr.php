<?php
/**
 * STDERR log writer. Writes out messages to STDERR.
 *
 * @package    Elixir
 * @category   Logging
 * @author    Not well-known man
 * @copyright  (c) 2016-2017 Elixir Team
 * @license
 */
class Log_StdErr extends Log_Writer {
	/**
	 * Writes each of the messages to STDERR.
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
			fwrite(STDERR, $this->format_message($message).PHP_EOL);
		}
	}

}
