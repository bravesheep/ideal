<?php

namespace Bs\Ideal\Request;

use Bs\Ideal\Ideal;

class DirectoryRequest extends Request
{
    const ROOT_NAME = 'DirectoryReq';

    public function __construct(Ideal $ideal)
    {
        parent::__construct($ideal, self::ROOT_NAME);
    }
}
