<?php

namespace Bs\Ideal;

use ass\XmlSecurity\Key;

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
}
