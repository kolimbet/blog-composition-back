<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Thrown when the request data does not match this already recorded in the database and cannot be overwritten
 */
class DataConflictException extends Exception
{
  public function __construct(string $message = 'The request data does not match those already recorded in the database', int $code = 409, Throwable|null $previous = null )
  {
    parent::__construct($message, $code, $previous);
  }
}