<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Throwable;

class FailedDeletingDirectoryException extends FileException
{
  public function __construct(string $message = 'Failed deleting directory', int $code = 500, Throwable|null $previous = null )
  {
    parent::__construct($message, $code, $previous);
  }
}