<?php

namespace Bs\IDeal\Response;

class StatusResponse extends Response
{
    public function getAcquirerId()
    {
        return $this->singleValue('//i:Acquirer/i:acquirerID');
    }
}
