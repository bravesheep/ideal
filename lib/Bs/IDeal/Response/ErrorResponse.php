<?php

namespace Bs\IDeal\Response;

class ErrorResponse extends Response
{
    public function getErrorCode()
    {
        return $this->singleValue('//i:errorCode');
    }

    public function getErrorMessage()
    {
        return $this->singleValue('//i:errorMessage');
    }

    public function getErrorDetail()
    {
        return $this->singleValue('//i:errorDetail');
    }

    public function getConsumerMessage()
    {
        return $this->singleValue('//i:consumerMessage');
    }
}
