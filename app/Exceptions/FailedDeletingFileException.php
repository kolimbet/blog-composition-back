<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Throwable;

class FailedDeletingFileException extends FileException
{
  public function __construct(string $message = 'Failed deleting file', int $code = 500, Throwable|null $previous = null )
  {
    parent::__construct($message, $code, $previous);
  }
}