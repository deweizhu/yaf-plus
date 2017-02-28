<?php

/**
 * MethodNotAllowedHttpException.
 *
 * @author 知名不具
 */
class Exception_MethodNotAllowedHttpException extends Exception_HttpException
{
    /**
     * Constructor.
     *
     * @param array      $allow    An array of allowed methods
     * @param string     $message  The internal exception message
     * @param \Exception $previous The previous exception
     * @param int        $code     The internal exception code
     */
    public function __construct(array $allow, $message = null, Exception $previous = null, $code = 0)
    {
        $headers = array('Allow' => strtoupper(implode(', ', $allow)));

        parent::__construct(405, $message, $previous, $headers, $code);
    }
}
