<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Thrown when the request to create || insert || update || delete the DB failed
 */
class FailedRequestDBException extends Exception
{
  public function __construct(string $message = 'Failed request to DB', int $code = 500, Throwable|null $previous = null )
  {
    parent::__construct($message, $code, $previous);
  }
}
