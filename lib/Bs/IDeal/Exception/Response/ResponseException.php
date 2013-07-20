<?php

namespace Bs\IDeal\Exception\Response;

use Bs\Ideal\Exception\IDealException;
use DOMDocument;

class ResponseException extends IDealException
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
