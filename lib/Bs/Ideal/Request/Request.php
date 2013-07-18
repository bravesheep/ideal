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

    public function __construct(Ideal $ideal, $rootName)
    {
        $this->ideal = $ideal;
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
    }

    public function sign()
    {
        $key = $this->getIdeal()->getKey();
        $signature = DSig::createSignature($key, DSig::EXC_C14N, $this->root);
        // $node = $this->doc->createElement('test');
        // $transformationAlgorithm = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
        // DSig::addNodeToSignature($signature, $node, $digestAlgorithm, DSig::XPATH, array(
        //     'xpath_transformation' => array(
        //         'query' => 'count(ancestor-or-self::dsig:Signature | here()/ancestor::dsig:Signature[1]) > count(ancestor-or-self::dsig:Signature)',
        //         'namespaces' => array(
        //             'dsig' => $transformationAlgorithm
        //         )
        //     )
        // ));
        DSig::addNodeToSignature($signature, $this->root, DSig::SHA256, DSig::TRANSFORMATION_ENVELOPED_SIGNATURE);
        DSig::signDocument($signature, $key, DSig::EXC_C14N);
    }

    public function send()
    {
        $curl = curl_init($this->getIdeal()->getBaseUrl());
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->doc->saveXML());
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type' => 'text/xml; charset=UTF-8'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curl);

        print $result;
    }

    public function getDocument()
    {
        return $this->doc;
    }
}
