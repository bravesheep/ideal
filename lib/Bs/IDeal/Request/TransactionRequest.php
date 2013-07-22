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
        $this->setLanguage('nl');
        $this->setCurrency('EUR');
        $this->setTimeoutAt(new DateTime('now + 15 minutes'));
    }

    public function setAmount($cents)
    {
        $cents = sprintf('%03d', $cents);
        $coins = substr($cents, 0, -2);
        $cents = substr($cents, -2);
        $this->amount = sprintf('%s.%s', $coins, $cents);
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
        $now = new DateTime('now');
        $diff = $time->diff($now, true);
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
        $this->timeout = $stat;
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
        $transaction->appendChild($this->createElement('amount', $this->amount));
        $transaction->appendChild($this->createElement('currency', $this->currency));
        $transaction->appendChild($this->createElement('expirationPeriod', $this->timeout));
        $transaction->appendChild($this->createElement('language', $this->language));
        $transaction->appendChild($this->createElement('description', $this->description));
        $transaction->appendChild($this->createElement('entranceCode', $this->entranceCode));
        $this->root->appendChild($transaction);
    }
}
