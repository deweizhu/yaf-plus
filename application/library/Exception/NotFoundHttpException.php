<?php

/**
 * NotFoundHttpException.
 *
 * @author Not well-known man
 */
class Exception_NotFoundHttpException extends Exception_HttpException
{
    /**
     * Constructor.
     *
     * @param string     $message  The internal exception message
     * @param \Exception $previous The previous exception
     * @param int        $code     The internal exception code
     */
    public function __construct($message = null, Exception $previous = null, $code = 0)
    {
        parent::__construct(404, $message, $previous, array(), $code);
    }
}
