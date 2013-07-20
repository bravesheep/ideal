<?php

namespace Bs\IDeal;

use ass\XmlSecurity\Key;
use Bs\IDeal\Exception;
use Bs\IDeal\Request;
use DOMDocument;

class IDeal
{
    const VERSION = "3.3.1";

    private $merchantId;

    private $subId;

    private $merchantKey;

    private $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function setMerchant($merchantId, $subId = 0)
    {
        $this->merchantId = $merchantId;
        $this->subId = $subId;
    }

    public function setMerchantKey($key, $passphrase = null, $isFile = true)
    {
        $this->merchantKey = Key::factory(Key::RSA_SHA256, $key, $isFile, Key::TYPE_PRIVATE, $passphrase);
    }

    public function getMerchantId()
    {
        return $this->merchantId;
    }

    public function getSubId()
    {
        return $this->subId;
    }

    public function getMerchantKey()
    {
        return $this->merchantKey;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function createDirectoryRequest()
    {
        return new Request\DirectoryRequest($this);
    }

    public function createTransactionRequest()
    {
        // TODO
    }

    public function send(Request\Request $request)
    {
        if (!$request->isSigned()) {
            $request->sign();
        }

        $curl = curl_init($this->getBaseUrl());
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getDocumentString());
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type' => 'text/xml; charset=UTF-8'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        print $request->getDocumentString();
        $result = curl_exec($curl);
        return $this->handleResult($result);
    }

    protected function handleResult($document)
    {
        print $document; exit;
        $doc = new DOMDocument();
        if ($doc->loadXML($document)) {
            switch ($doc->documentElement->tagName) {
                case 'AcquirerErrorRes':
                    throw new Exception\Response\AcquirerException($doc);
                default:
                    throw new Exception\UnknownResponseException();
            }
        } else {
            // TODO: add parsing error info
            throw new Exception\InvalidXMLException();
        }
    }
}
