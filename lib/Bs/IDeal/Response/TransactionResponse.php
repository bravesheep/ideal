<?php

namespace Bs\IDeal\Response;

use DateTime;

class TransactionResponse extends Response
{
    public function getAcquirerId()
    {
        return $this->singleValue('//i:Acquirer/i:acquirerID');
    }

    public function getTransactionId()
    {
        return $this->singleValue('//i:Transaction/i:transactionID');
    }

    public function getAuthenticationUrl()
    {
        return $this->singleValue('//i:Issuer/i:issuerAuthenticationURL');
    }

    public function getTransactionDateTime()
    {
        return new DateTime($this->singleValue('//i:Transaction/i:transactionCreateDateTimestamp'));
    }

    public function getPurchaseId()
    {
        return $this->singleValue('//i:Issuer/i:purchaseID');
    }
}
