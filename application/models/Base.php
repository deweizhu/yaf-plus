<?php

/**
 *
 * @author: ZDW
 * @date: 2015-11-18
 * @version: $Id: Base.php 12655 2015-11-21 09:36:42Z zdw $
 */
class BaseModel
{
    protected $request = NULL; // Request
    protected $session = NULL; // Session
    protected $db = NULL; //

    public function __construct($domain = NULL)
    {

        $this->db = Database::instance();

    }
}