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
        $this->root->setAttribute('version', IDeal::VERSION);

        // add timestamp request is created
        $now = gmdate('Y-m-d\TH:i:s.0\Z');
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
        $key = $this->ideal->getMerchantPrivateKey();

        // sign document
        $dsig = new XMLSecurityDSig();
        $dsig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $dsig->addReference($this->doc, XMLSecurityDSig::SHA256, ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'], ['force_uri' => true]);
        $dsig->sign($key);
        $signature = $dsig->appendSignature($this->root);

        // add keyinfo
        $thumbprint = $this->ideal->getMerchantCertificate()->getX509Thumbprint();
        $keyName = $this->doc->createElementNS(XMLSecurityDSig::XMLDSIGNS, 'KeyName', $thumbprint);
        $keyInfo = $this->doc->createElementNS(XMLSecurityDSig::XMLDSIGNS, 'KeyInfo');
        $keyInfo->appendChild($keyName);
        $signature->appendChild($keyInfo);

        // generate KeyInfo node
        // $thumbprint = $this->ideal->getMerchantCertificate()->getX509Thumbprint();
        // $keyName = $this->doc->createElementNS(DSig::NS_XMLDSIG, 'KeyName', $thumbprint);
        // $keyInfo = $this->doc->createElementNS(DSig::NS_XMLDSIG, 'KeyInfo');
        // $keyInfo->appendChild($keyName);

        // sign
        // $signature = DSig::createSignature($key, DSig::EXC_C14N, $this->root, null, $keyInfo);
        // DSig::addNodeToSignature($signature, $this->doc, DSig::SHA256, DSig::TRANSFORMATION_ENVELOPED_SIGNATURE, [
        //     'force_uri' => true,
        // ]);
        // DSig::signDocument($signature, $key, DSig::EXC_C14N);

        $this->signed = true;
        print $this->getDocumentString();
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
