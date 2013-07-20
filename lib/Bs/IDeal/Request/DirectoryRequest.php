<?php

namespace Bs\IDeal\Request;

use Bs\IDeal\IDeal;

class DirectoryRequest extends Request
{
    const ROOT_NAME = 'DirectoryReq';

    public function __construct(IDeal $ideal)
    {
        parent::__construct($ideal, self::ROOT_NAME);
    }
}
