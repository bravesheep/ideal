<?php

namespace Bs\Ideal;

use ass\XmlSecurity\Key;
use Bs\Ideal\Request;
use Bs\Ideal\Exception;
use DOMDocument;

class Ideal
{
    const VERSION = "3.3.1";

    private $merchantId;

    private $key;

    private $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function setMerchant($merchantId, Key $key)
    {
        $this->merchantId = $merchantId;
        $this->key = $key;
    }

    public function getMerchantId()
    {
        return $this->merchantId;
    }

    public function getKey()
    {
        return $this->key;
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

    }

    public function send(Request\Request $request)
    {
        if (!$request->isSigned()) {
            $request->sign();
        }

        $curl = curl_init($this->getBaseUrl());
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getDocument()->saveXML());
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type' => 'text/xml; charset=UTF-8'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curl);
        return $this->handleResult($result);
    }

    protected function handleResult($document)
    {
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
