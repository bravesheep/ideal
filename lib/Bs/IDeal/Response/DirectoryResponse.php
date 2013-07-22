<?php

namespace Bs\IDeal\Response;

class DirectoryResponse extends Response
{
    public function getIssuers($country)
    {
        $issuers = [];
        $query = '//i:Directory/i:Country/i:Issuer[../i:countryNames[text()="' . $country . '"]]';
        foreach ($this->query($query) as $issuer) {
            $id = $this->singleValue('./i:issuerID', $issuer);
            $name = $this->singleValue('./i:issuerName', $issuer);
            $issuers[$id] = $name;
        }
        return $issuers;
    }

    public function getCountries()
    {
        $countries = [];
        foreach ($this->query('//i:Directory/i:Country/i:countryNames') as $country) {
            $countries[] = $country->nodeValue;
        }
        return $countries;
    }

    public function getAllIssuers()
    {
        $issuers = [];
        foreach ($this->getCountries() as $country) {
            $issuers[$country] = $this->getIssuers($country);
        }
        return $issuers;
    }

    public function getAcquirerId()
    {
        return $this->singleValue('//i:Acquirer/i:acquirerID');
    }
}
