<?php 

namespace App\Exception;

use Exception;
use Throwable;

class MissingMandatoryAttributeException extends Exception implements Throwable
{
    public function __construct(string $missingDataKey)
    {
        parent::__construct(sprintf('Missing mandatory data for field: %s', $missingDataKey));
    }
}