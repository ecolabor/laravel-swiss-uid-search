<?php

declare(strict_types=1);

namespace Ecolabor\SwissUid\Exceptions;

use Exception;

class UidNotFoundException extends Exception
{
    public function __construct(string $uid)
    {
        parent::__construct("UID '{$uid}' was not found in the register.");
    }
}
