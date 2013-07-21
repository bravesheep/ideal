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

    private $merchantPrivateKey;

    private $merchantCertificate;

    private $baseUrl;

    private $verification = true;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function setMerchant($merchantId, $subId = 0)
    {
        $this->merchantId = $merchantId;
        $this->subId = $subId;
    }

    public function setMerchantPrivateKey($key, $passphrase = null, $isFile = true)
    {
        $this->merchantPrivateKey = Key::factory(Key::RSA_SHA256, $key, $isFile, Key::TYPE_PRIVATE, $passphrase);
    }

    public function setMerchantCertificate($key, $isFile = true)
    {
        $this->merchantCertificate = Key::factory(Key::RSA_SHA256, $key, $isFile, Key::TYPE_PUBLIC);
    }

    public function disableVerification()
    {
        $this->verification = false;
    }

    public function getMerchantId()
    {
        return $this->merchantId;
    }

    public function getSubId()
    {
        return $this->subId;
    }

    public function getMerchantPrivateKey()
    {
        return $this->merchantPrivateKey;
    }

    public function getMerchantCertificate()
    {
        return $this->merchantCertificate;
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
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getDocumentString());
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type' => 'text/xml; charset=UTF-8'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if ($this->verification === false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        // print $request->getDocumentString(); exit;

        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headers = explode("\r\n", substr($response, 0, $header_size));
        $body = substr($response, $header_size);

        return $this->handleResult($headers, $body);
    }

    protected function handleResult($headers, $document)
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
