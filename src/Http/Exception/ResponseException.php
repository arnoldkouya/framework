<?php

namespace Bow\Http\Exception;

use ErrorException;

class ResponseException extends ErrorException
{
    /**
     * The response code
     *
     * @var int
     */
    protected $code;

    /**
     * Set the http code
     *
     * @param int $code
     * @return void
     */
    public function setCode(int $code)
    {
        $this->code = $code;
    }
}
