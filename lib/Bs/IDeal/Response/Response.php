<?php

namespace Bs\IDeal\Response;

use Bs\IDeal\Exception;
use Bs\IDeal\IDeal;
use DOMDocument;
use DOMNode;
use DOMXPath;
use XMLSecurityDSig;
use DateTime;

class Response
{
    protected $doc;

    protected $root;

    protected $ideal;

    protected $xpath;

    private $isVerified;

    private $verificationCompleted;

    public function __construct(IDeal $ideal, DOMDocument $document)
    {
        $this->doc = $document;
        $this->root = $this->doc->documentElement;
        $this->ideal = $ideal;
        $this->xpath = new DOMXPath($this->doc);
        $rootNamespace = $this->doc->lookupNamespaceUri($this->doc->namespaceURI);
        $this->xpath->registerNamespace('i', $rootNamespace);
        $this->verificationCompleted = false;
    }

    public function getDocument()
    {
        return $this->doc;
    }

    public function verify($throwException = false)
    {
        if ($this->verificationCompleted === false) {
            $cert = $this->ideal->getAcquirerCertificate();
            $this->isVerified = $this->ideal->verify($this->doc, $cert, $throwException);
            $this->verificationCompleted = true;
        }

        if ($throwException && $this->isVerified === false) {
            throw new Exception\SecurityException('Could not validate response');
        }
        return $this->isVerified;
    }

    protected function query($query, DOMNode $node = null)
    {
        if ($node === null) {
            return $this->xpath->query($query);
        } else {
            return $this->xpath->query($query, $node);
        }
    }

    protected function single($query, DOMNode $node = null)
    {
        $nodes = $this->query($query, $node);
        if ($nodes->length <= 0) {
            throw new Exception\InvalidXMLException(sprintf('Could not find node matching query "%s"', $query));
        }
        return $nodes->item(0);
    }

    protected function singleValue($query, DOMNode $node = null)
    {
        return $this->single($query, $node)->nodeValue;
    }

    public function getDateTime()
    {
        return new DateTime($this->singleValue('//i:createDateTimestamp'));
    }
}
