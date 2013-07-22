<?php

namespace Bs\IDeal\Exception;

class NoSuccessException extends IDealException
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
}
