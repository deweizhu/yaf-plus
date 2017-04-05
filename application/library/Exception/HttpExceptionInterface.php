<?php

/**
 * Interface for HTTP error exceptions.
 *
 * @author 知名不具
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
