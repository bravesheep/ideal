<?php

namespace Bs\Ideal\Exception\Response;

use Bs\Ideal\Exception\IdealException;
use DOMDocument;

class ResponseException extends IdealException
{
    protected $doc;

    public function __construct(DOMDocument $document)
    {
        $this->doc = $document;
    }

    public function getResponseDocument()
    {
        return $this->doc;
    }
}
