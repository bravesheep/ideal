<?php

namespace Bs\IDeal\Request;

use Bs\IDeal\IDeal;
use DateTime;

class TransactionRequest extends Request
{
    const ROOT_NAME = 'AcquirerTrxReq';

    private $amount;

    private $purchaseId;

    private $currency;

    private $timeout;

    private $language;

    private $description;

    private $entranceCode;

    private $returnUrl;

    private $issuer;

    public function __construct(IDeal $ideal)
    {
        parent::__construct($ideal, self::ROOT_NAME);
        $this->setLanguage(IDeal::DUTCH);
        $this->setCurrency(IDeal::EURO);
        $this->setTimeoutAt(new DateTime('now + 15 minutes'));
    }

    public function setAmount($cents)
    {
        $this->amount = $cents;
    }

    public function setPurchaseId($id)
    {
        $this->purchaseId = $id;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    public function setTimeoutAt(DateTime $time)
    {
        $this->timeout = $time;
    }

    public function setLanguage($lang)
    {
        $this->language = $lang;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setEntranceCode($code)
    {
        $this->entranceCode = $code;
    }

    public function setReturnUrl($url)
    {
        $this->returnUrl = $url;
    }

    public function setIssuer($id)
    {
        $this->issuer = $id;
    }

    protected function preSign()
    {
        $this->merchant->appendChild($this->createElement('merchantReturnURL', $this->returnUrl));

        $issuer = $this->createElement('Issuer');
        $issuer->appendChild($this->createElement('issuerID', $this->issuer));
        $this->root->insertBefore($issuer, $this->merchant);

        $transaction = $this->createElement('Transaction');
        $transaction->appendChild($this->createElement('purchaseID', $this->purchaseId));
        $transaction->appendChild($this->createElement('amount', $this->getInternalAmount()));
        $transaction->appendChild($this->createElement('currency', $this->currency));
        $transaction->appendChild($this->createElement('expirationPeriod', $this->getExpirationPeriod()));
        $transaction->appendChild($this->createElement('language', $this->language));
        $transaction->appendChild($this->createElement('description', $this->description));
        $transaction->appendChild($this->createElement('entranceCode', $this->entranceCode));
        $this->root->appendChild($transaction);
    }

    public function getInternalAmount()
    {
        $cents = sprintf('%03d', $this->amount);
        $coins = substr($cents, 0, -2);
        $cents = substr($cents, -2);
        return sprintf('%s.%s', $coins, $cents);
    }

    public function getExpirationPeriod()
    {
        $now = new DateTime('now');
        $diff = $this->timeout->diff($now, true);
        $stat = 'P';
        if ($diff->y > 0) {
            $stat .= $diff->y . 'Y';
        }

        if ($diff->m > 0) {
            $stat .= $diff->m . 'M';
        }

        if ($diff->d > 0) {
            $stat .= $diff->d . 'D';
        }

        $stat .= 'T';

        if ($diff->h > 0) {
            $stat .= $diff->h . 'H';
        }

        if ($diff->i > 0) {
            $stat .= $diff->i . 'M';
        }

        if ($diff->s > 0) {
            $stat .= $diff->s . 'S';
        }
        return $stat;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    public function getPurchaseId()
    {
        return $this->purchaseId;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getEntranceCode()
    {
        return $this->entranceCode;
    }

    public function getIssuer()
    {
        return $this->issuer;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getTimeoutAt()
    {
        return $this->timeout;
    }


}
