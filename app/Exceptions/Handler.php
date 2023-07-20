<?php

namespace App\Exceptions;

use Config;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
  /**
   * A list of exception types with their corresponding custom log levels.
   *
   * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
   */
  protected $levels = [
    //
  ];

  /**
   * A list of the exception types that are not reported.
   *
   * @var array<int, class-string<\Throwable>>
   */
  protected $dontReport = [
    //
  ];

  /**
   * A list of the inputs that are never flashed to the session on validation exceptions.
   *
   * @var array<int, string>
   */
  protected $dontFlash = [
    'current_password',
    'password',
    'password_confirmation',
  ];

  /**
   * Register the exception handling callbacks for the application.
   *
   * @return void
   */
  public function register()
  {
    $this->reportable(function (Throwable $e) {
      //
    });
  }

  public function render($request, Throwable $e)
  {
    $error = $this->convertExceptionToResponse($e);
    $response = [];
    if ($error->getStatusCode() == 500) {
      $response['error'] = $e->getMessage();
      if (Config::get('app.debug')) {
        $response['trace'] = $e->getTraceAsString();
        $response['code'] = $e->getCode();
      }
    }
    return response()->json($response, $error->getStatusCode());
  }
}