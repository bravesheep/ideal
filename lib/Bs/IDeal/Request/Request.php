<?php

namespace Bs\IDeal\Request;

use ass\XmlSecurity\DSig;
use Bs\IDeal\IDeal;
use DOMImplementation;

class Request
{
    const XMLNS = "http://www.idealdesk.com/ideal/messages/mer-acq/3.3.1";

    protected $ideal;

    protected $doc;

    protected $root;

    private $signed;

    public function __construct(IDeal $ideal, $rootName)
    {
        $this->ideal = $ideal;
        $this->signed = false;
        $this->createDocument($rootName);
    }

    public function getIdeal()
    {
        return $this->ideal;
    }

    private function createDocument($rootName)
    {
        $implementor = new DOMImplementation();
        $this->doc = $implementor->createDocument(self::XMLNS, $rootName);

        $this->doc->version = '1.0';
        $this->doc->encoding = 'UTF-8';
        $this->doc->formatOutput = true;

        $this->root = $this->doc->documentElement;
        $this->root->setAttribute('version', Ideal::VERSION);

        // add timestamp request is created
        $now = gmdate('o-m-d\TH:i:s.000\Z');
        $created = $this->doc->createElement('createDateTimestamp', $now);
        $this->root->appendChild($created);

        // add merchant information
        $merchant = $this->doc->createElement('Merchant');
        $merchant->appendChild(
            $this->doc->createElement(
                'merchantID',
                sprintf('%09d', $this->ideal->getMerchantId())
            )
        );
        $merchant->appendChild($this->doc->createElement('subID', $this->ideal->getSubId()));
        $this->root->appendChild($merchant);
    }

    public function sign()
    {
        $key = $this->ideal->getMerchantKey();

        // sign
        $signature = DSig::createSignature($key, DSig::EXC_C14N, $this->doc);
        DSig::addNodeToSignature($signature, $this->doc, DSig::SHA256, DSig::TRANSFORMATION_ENVELOPED_SIGNATURE, array(
            'overwrite_id' => false,
        ));
        DSig::signDocument($signature, $key, DSig::EXC_C14N);

        $this->signed = true;
    }

    public function isSigned()
    {
        return $this->signed;
    }

    public function getDocument()
    {
        return $this->doc;
    }

    public function getDocumentString()
    {
        return $this->getDocument()->saveXML();
    }
}
