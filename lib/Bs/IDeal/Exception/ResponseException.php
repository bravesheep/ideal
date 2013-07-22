<?php

namespace Bs\IDeal\Exception;

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

    public function getErrorCode()
    {
        return $this->response->getErrorCode();
    }

    public function getErrorMessage()
    {
        return $this->response->getErrorMessage();
    }

    public function getErrorDetail()
    {
        return $this->response->getErrorDetail();
    }

    public function getConsumerMessage()
    {
        return $this->response->getConsumerMessage();
    }
}
