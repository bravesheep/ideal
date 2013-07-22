<?php

namespace Bs\IDeal;

// use ass\XmlSecurity\Key;
use Bs\IDeal\Exception;
use Bs\IDeal\Request;
use Bs\IDeal\Response;
use DOMDocument;
use XMLSecurityDSig;
use XMLSecurityKey;

class IDeal
{
    const VERSION = "3.3.1";

    private $merchantId;

    private $subId;

    private $merchantPrivateKey;

    private $merchantCertificate;

    private $acquirerCertificate;

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
        $this->merchantPrivateKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $this->merchantPrivateKey->passphrase = $passphrase;
        $this->merchantPrivateKey->loadKey($key, $isFile);

        // $this->merchantPrivateKey = Key::factory(Key::RSA_SHA256, $key, $isFile, Key::TYPE_PRIVATE, $passphrase);
    }

    public function setMerchantCertificate($key, $isFile = true)
    {
        $this->merchantCertificate = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
        $this->merchantCertificate->loadKey($key, $isFile, true);

        // $this->merchantCertificate = Key::factory(Key::RSA_SHA256, $key, $isFile, Key::TYPE_PUBLIC);
    }

    public function setAcquirerCertificate($key, $isFile = true)
    {
        $this->acquirerCertificate = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
        $this->acquirerCertificate->loadKey($key, $isFile, true);

        // $this->acquirerCertificate = Key::factory(Key::RSA_SHA256, $key, $isFile, Key::TYPE_PUBLIC);
    }

    public function disableVerification()
    {
        $this->verification = false;
    }

    public function verificationDisabled()
    {
        return !$this->verification;
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

    public function getAcquirerCertificate()
    {
        return $this->acquirerCertificate;
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

    public function verify(DOMDocument $document, XMLSecurityKey $cert, $throwException = false)
    {
        if (!$this->verification) {
            return true;
        } else {
            $dsig = new XMLSecurityDSig();
            $signature = $dsig->locateSignature($document);
            if (!$signature) {
                if ($throwException) {
                    throw new Exception\SecurityException('No signature element');
                }
                return false;
            }

            $dsig->canonicalizeSignedInfo();
            if (!$dsig->validateReference()) {
                if ($throwException) {
                    throw new Exception\SecurityException('Reference for signature invalid');
                }
                return false;
            }

            if (!$dsig->verify($cert)) {
                if ($throwException) {
                    throw new Exception\SecurityException('Invalid signature');
                }
                return false;
            }
            return true;
        }
    }

    protected function handleResult($headers, $document)
    {
        $doc = new DOMDocument();
        if ($doc->loadXML($document)) {
            switch ($doc->documentElement->tagName) {
                case 'AcquirerErrorRes':
                    throw new Exception\Response\AcquirerException(new Response\Response($this, $doc));
                default:
                    throw new Exception\UnknownResponseException();
            }
        } else {
            // TODO: add parsing error info
            throw new Exception\InvalidXMLException();
        }
    }
}
