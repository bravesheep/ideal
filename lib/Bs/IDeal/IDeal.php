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

    private $verification;

    private $autoVerify;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->autoVerify = true;
        $this->verification = true;
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

    public function disableAutoVerify()
    {
        $this->autoVerify = false;
    }

    public function verificationDisabled()
    {
        return !$this->verification;
    }

    public function doesAutoVerify()
    {
        return $this->autoVerify;
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
        return $this->handleResult('', '<?xml version="1.0" encoding="UTF-8"?><DirectoryRes xmlns="http://www.idealdesk.com/ideal/messages/mer-acq/3.3.1" xmlns:ns2="http://www.w3.org/2000/09/xmldsig#" version="3.3.1">
    <createDateTimestamp>2013-07-22T18:26:11.249Z</createDateTimestamp>
    <Acquirer>
        <acquirerID>0020</acquirerID>
    </Acquirer>
    <Directory>
        <directoryDateTimestamp>2013-07-22T18:26:11.249Z</directoryDateTimestamp>
        <Country>
            <countryNames>Deutschland</countryNames>
            <Issuer>
                <issuerID>INGBNL2A</issuerID>
                <issuerName>Issuer Simulation V3 - ING</issuerName>
            </Issuer>
            <Issuer>
                <issuerID>RABONL2U</issuerID>
                <issuerName>Issuer Simulation V3 - RABO</issuerName>
            </Issuer>
        </Country>
    </Directory>
<Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo><CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/><SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/><Reference URI=""><Transforms><Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/></Transforms><DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/><DigestValue>1VNbupLugve7a2/ln8VVbyVOAGCA3PA8yfKctaSH4/k=</DigestValue></Reference></SignedInfo><SignatureValue>YjwONW7pVo0vAY41Nl3q8CG/chiIpiug4mY1mC+cv4Mga43Q0dsM23A9OSTkRxHSdgNcq2XoAnyq
tWYCLWl9Z60/Cjz9GWRyc2TetHlgIBhb0RakPCzFb3UjnmXZsLUwCNdoq/ydiEDJEVdM2e90StWS
eM5D+t9aIfd65X54MejlEpWu96oea3kSvvFJrKpcfLeoLYF/stKMfqlsxrGRgAWkV+4k/NT3rkBD
wkRhUUt/Z67gPd+eAd669BIAsRBjFBNZYyn6yIccwLXhvK3/qSUgbhMWkccdVZBBTQAFZIH/Ff9t
sPAshOx1sbehdBkjD3QXwT4+qjUZ7qrbS1fvOw==</SignatureValue><KeyInfo><KeyName>FC0A17A7ABD72369726EA4D4DBEF9838128A7C78</KeyName></KeyInfo></Signature></DirectoryRes>');

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
            $response = null;
            switch ($doc->documentElement->tagName) {
                case 'AcquirerErrorRes':
                    $response = new Response\ErrorResponse($this, $doc);
                    break;
                case 'DirectoryRes':
                    $response = new Response\DirectoryResponse($this, $doc);
                    break;
                default:
                    throw new Exception\UnknownResponseException();
            }

            if ($this->doesAutoVerify()) {
                $response->verify(true);
            }

            if ($response instanceof Response\ErrorResponse) {
                throw new Exception\ResponseException($response);
            }
            return $response;
        } else {
            // TODO: add parsing error info
            throw new Exception\InvalidXMLException();
        }
    }
}
