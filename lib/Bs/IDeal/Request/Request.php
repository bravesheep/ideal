<?php

namespace Bs\IDeal\Request;

// use ass\XmlSecurity\DSig;
use Bs\IDeal\IDeal;
use DOMImplementation;
use XMLSecurityDSig;

class Request
{
    const XMLNS = "http://www.idealdesk.com/ideal/messages/mer-acq/3.3.1";

    protected $ideal;

    protected $doc;

    protected $root;

    private $signed;

    protected $merchant;

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
        $this->doc->preserveWhiteSpace = false;
        $this->doc->formatOutput = false;

        $this->root = $this->doc->documentElement;
        $this->root->setAttribute('version', IDeal::VERSION);

        // add timestamp request is created
        $now = gmdate('Y-m-d\TH:i:s.000\Z');
        $created = $this->createElement('createDateTimestamp', $now);
        $this->root->appendChild($created);

        // add merchant information
        $this->merchant = $this->createElement('Merchant');
        $this->merchant->appendChild(
            $this->createElement(
                'merchantID',
                sprintf('%09d', $this->ideal->getMerchantId())
            )
        );
        $this->merchant->appendChild($this->createElement('subID', $this->ideal->getSubId()));
        $this->root->appendChild($this->merchant);
    }

    protected function createElement($name, $value = null)
    {
        if ($value === null) {
            $element = $this->doc->createElementNS(self::XMLNS, $name);
        } else {
            $element = $this->doc->createElementNS(self::XMLNS, $name, $value);
        }
        return $element;
    }

    public function sign()
    {
        $this->preSign();
        $key = $this->ideal->getMerchantPrivateKey();

        // sign document
        $dsig = new XMLSecurityDSig();
        $dsig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $dsig->addReference($this->doc, XMLSecurityDSig::SHA256, ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'], ['force_uri' => true]);
        $dsig->sign($key, $this->root);
        $signature = $dsig->sigNode;

        // add keyinfo
        $thumbprint = $this->ideal->getMerchantCertificate()->getX509Thumbprint();
        $keyName = $dsig->createNewSignNode('KeyName', strtoupper($thumbprint));
        $keyInfo = $dsig->createNewSignNode('KeyInfo');
        $keyInfo->appendChild($keyName);
        $signature->appendChild($keyInfo);

        $this->signed = true;
    }

    protected function preSign()
    {
        // do nothing in standard implementation
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
        return $this->getDocument()->saveXML(null, LIBXML_NOEMPTYTAG);
    }

    public function send()
    {
        return $this->ideal->send($this);
    }
}
