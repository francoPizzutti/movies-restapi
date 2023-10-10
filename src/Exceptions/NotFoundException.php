<?php 

namespace App\Exception;

use Exception;
use Throwable;

class NotFoundException extends Exception implements Throwable
{
    public function __construct(string $entity)
    {
        parent::__construct(sprintf('%s not found for specified ID. Please check & retry.', $entity));
    }
}