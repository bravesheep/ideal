<?php

namespace Bs\IDeal\Exception\Response;

use Bs\IDeal\Exception\IDealException;
use Bs\IDeal\Response\Response;

class ResponseException extends IDealException
{
    protected $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function verify($throwException = false)
    {
        return $this->response->verify($throwException);
    }
}
