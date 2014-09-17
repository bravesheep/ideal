<?php

namespace Bs\IDeal\Response;

use DateTime;

class StatusResponse extends Response
{
    public function getAcquirerId()
    {
        return $this->singleValue('//i:Acquirer/i:acquirerID');
    }

    public function getTransactionId()
    {
        return $this->singleValue('//i:Transaction/i:transactionID');
    }

    public function getStatus()
    {
        return $this->singleValue('//i:Transaction/i:status');
    }

    public function getStatusDateTime()
    {
        return new DateTime($this->singleValue('//i:Transaction/i:statusDateTimestamp'));
    }

    public function getConsumerName()
    {
        return $this->singleValue('//i:Transaction/i:consumerName');
    }

    public function getConsumerIBAN()
    {
        return $this->singleValue('//i:Transaction/i:consumerIBAN');
    }

    public function getConsumerBIC()
    {
        return $this->singleValue('//i:Transaction/i:consumerBIC');
    }

    public function getInternalAmount()
    {
        return $this->singleValue('//i:Transaction/i:amount');
    }

    public function getAmount()
    {
        $val = $this->getInternalAmount();
        $parts = explode('.', $val);
        if (count($parts) < 2) {
            return intval($parts[0]) * 100;
        } else {
            return intval($parts[0]) * 100 + intval($parts[1]);
        }
    }

    public function getCurrency()
    {
        return $this->singleValue('//i:Transaction/i:currency');
    }
}
