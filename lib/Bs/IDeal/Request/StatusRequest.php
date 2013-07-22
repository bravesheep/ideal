<?php

namespace Bs\IDeal\Request;

use Bs\IDeal\IDeal;

class StatusRequest extends Request
{
    const ROOT_NAME = 'AcquirerStatusReq';

    private $transactionId;

    public function __construct(IDeal $ideal)
    {
        parent::__construct($ideal, self::ROOT_NAME);
    }

    public function setTransactionId($id)
    {
        $this->transactionId = $id;
    }

    protected function preSign()
    {
        $transaction = $this->createElement('Transaction');
        $transaction->appendChild($this->createElement('transactionID', $this->transactionId));
        $this->root->appendChild($transaction);
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }
}
