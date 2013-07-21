<?php

namespace Bs\IDeal\Response;

use Bs\IDeal\Exception;
use Bs\IDeal\IDeal;
use DOMDocument;
use XMLSecurityDSig;

class Response
{
    protected $doc;

    protected $ideal;

    public function __construct(IDeal $ideal, DOMDocument $document)
    {
        $this->doc = $document;
        $this->ideal = $ideal;
    }

    public function getDocument()
    {
        return $this->doc;
    }

    public function verify($throwException = false)
    {
        $cert = $this->ideal->getAcquirerCertificate();
        return $this->ideal->verify($this->doc, $cert, $throwException);
    }
}
