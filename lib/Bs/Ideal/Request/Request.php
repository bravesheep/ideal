<?php

namespace Bs\Ideal\Request;

use ass\XmlSecurity\DSig;
use Bs\Ideal\Ideal;
use \DOMImplementation;

class Request
{
    const XMLNS = "http://www.idealdesk.com/ideal/messages/mer-acq/3.3.1";

    private $ideal;

    private $doc;

    private $root;

    private $merchant;

    private $signed;

    public function __construct(Ideal $ideal, $rootName)
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

        $now = gmdate('o-m-d\TH:i:s.000\Z');
        $created = $this->doc->createElement('createDateTimestamp', $now);
        $this->root->appendChild($created);

        $this->merchant = $this->doc->createElement('Merchant');
        $this->root->appendChild($this->merchant);

        $merchantId = $this->doc->createElement('merchantID', $this->getIdeal()->getMerchantId());
        $this->merchant->appendChild($merchantId);

        $subId = $this->doc->createElement('subID', $this->getIdeal()->getSubId());
        $this->merchant->appendChild($subId);
    }

    public function sign()
    {
        $key = $this->getIdeal()->getKey();
        $signature = DSig::createSignature($key, DSig::EXC_C14N, $this->root);
        DSig::addNodeToSignature($signature, $this->root, DSig::SHA256, DSig::TRANSFORMATION_ENVELOPED_SIGNATURE, array(
            'overwrite_id' => false,
        ));
        DSig::signDocument($signature, $key, DSig::EXC_C14N);
        $this->root->removeAttribute('Id');
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
        $str = $this->getDocument()->saveXML();
        return str_replace(array('<ds:', '</ds:', ' xmlns:ds="'), array('<', '</', ' xmlns="'), $str);
    }
}
