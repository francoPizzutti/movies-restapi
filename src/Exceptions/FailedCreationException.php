<?php 

namespace App\Exception;

use Exception;
use Throwable;

class FailedCreationException extends Exception implements Throwable
{
    public function __construct(string $entity, string $customMessage = 'Please review sent data.')
    {
        parent::__construct(sprintf('%s creation has fail. %s', $entity, $customMessage));
    }
}