<?php

/**
 * Interface for HTTP error exceptions.
 *
 * @author Not well-known man
 */
interface Exception_HttpExceptionInterface
{
    /**
     * Returns the status code.
     *
     * @return int An HTTP response status code
     */
    public function getStatusCode();

    /**
     * Returns response headers.
     *
     * @return array Response headers
     */
    public function getHeaders();
}
