<?php

namespace Bs\IDeal;

use Bs\IDeal\Exception;
use Bs\IDeal\Request;
use Bs\IDeal\Response;
use DOMDocument;
use XMLSecurityDSig;
use XMLSecurityKey;

class IDeal
{
    const VERSION = "3.3.1";

    const EURO = 'EUR';

    const DUTCH = 'nl';

    const ENGLISH = 'en';

    const SUCCESS = 'Success';

    const CANCELLED = 'Cancelled';

    const EXPIRED = 'Expired';

    const FAILURE = 'Failure';

    const OPEN = 'Open';

    private $merchantId;

    private $subId;

    private $merchantPrivateKey;

    private $merchantCertificate;

    private $acquirerCertificate;

    private $baseUrl;

    private $verification;

    private $autoVerify;

    private $failOnStatus;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->autoVerify = true;
        $this->verification = true;
        $this->failOnStatus = false;
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
    }

    public function setMerchantCertificate($key, $isFile = true)
    {
        $this->merchantCertificate = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
        $this->merchantCertificate->loadKey($key, $isFile, true);
    }

    public function setAcquirerCertificate($key, $isFile = true)
    {
        $this->acquirerCertificate = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
        $this->acquirerCertificate->loadKey($key, $isFile, true);
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

    public function failOnStatusNotSuccess($fail = true)
    {
        $this->failOnStatus = $fail;
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

    public function createTransactionRequest($issuer, $returnUrl, $id, $amount, $description)
    {
        $request = new Request\TransactionRequest($this);
        $request->setIssuer($issuer);
        $request->setReturnUrl($returnUrl);
        $request->setPurchaseId($id);
        $request->setAmount($amount);
        $request->setDescription($description);
        $request->setEntranceCode($this->getRandom(40));
        return $request;
    }

    public function createStatusRequest($transactionId)
    {
        $request = new Request\StatusRequest($this);
        $request->setTransactionId($transactionId);
        return $request;
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

        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headers = explode("\r\n", substr($response, 0, $header_size));
        $body = substr($response, $header_size);

        return $this->handleResult($request, $headers, $body);
    }

    protected function handleResult(Request\Request $request, $headers, $document)
    {
        $doc = new DOMDocument();
        if (!empty($document) && $doc->loadXML($document)) {
            $response = null;
            switch ($doc->documentElement->tagName) {
                case 'AcquirerErrorRes':
                    $response = new Response\ErrorResponse($this, $request, $doc);
                    break;
                case 'DirectoryRes':
                    $response = new Response\DirectoryResponse($this, $request, $doc);
                    break;
                case 'AcquirerTrxRes':
                    $response = new Response\TransactionResponse($this, $request, $doc);
                    break;
                case 'AcquirerStatusRes':
                    $response = new Response\StatusResponse($this, $request, $doc);
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

            if ($this->failOnStatus && $response instanceof Response\StatusResponse) {
                if ($response->getStatus() !== self::SUCCESS) {
                    throw new Exception\NoSuccessException($response);
                }
            }
            return $response;
        } else {
            throw new Exception\InvalidXMLException();
        }
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

    public function getRandom($length = 40)
    {
        $keys = array_merge(range(0,9), range('a', 'z'));
        $key = '';
        for($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }
        return $key;
    }
}
