<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Log;
use SebastianBergmann\CodeCoverage\Util\DirectoryCouldNotBeCreatedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
    //   $this->reportable(function (AuthenticationException $e, $request) {
    //     Log::info("Handler->register->AuthenticationException callback", [$e]);
    //   // if ($request->is('api/*')) {

    //     return response()->json([
    //       'status_code' => 401,
    //       'success' => false,
    //       'message' => 'Unauthenticated'
    //     ], 401);
    //   // }
    //  });
  }

  public function render($request, Throwable $e)
  {
    if ($e instanceof AuthenticationException) {
      return $this->customApiResponse($e, 'Unauthorized', 401);
    }
    if ($e instanceof AccessDeniedHttpException) {
      return $this->customApiResponse($e, '', 403);
    }

    if ($e instanceof BadRequestException) {
      return $this->customApiResponse($e, $e->getMessage() || 'Bad request', 400);
    }

    // An exception is thrown when the firsrOrFail() and findOrFail() methods fail
    if ($e instanceof ModelNotFoundException) {
      return $this->customApiResponse($e, $e->getMessage() || 'Record not found', 404);
    }
    if ($e instanceof RecordsNotFoundException) {
      return $this->customApiResponse($e, $e->getMessage() || 'Records not found', 404);
    }

    if ($e instanceof CannotWriteFileException) {
      return $this->customApiResponse($e, $e->getMessage() || 'Failed to write the file', 500);
    }
    if ($e instanceof DirectoryCouldNotBeCreatedException) {
      return $this->customApiResponse($e, $e->getMessage() || 'Failed to create a directory', 500);
    }


    return $this->customApiResponse($e);
  }

  private function customApiResponse($exception, $message = "", $statusCode = 0)
  {
    if (!$statusCode) {
      if (method_exists($exception, 'getCode')) {
        $statusCode = $exception->getCode();
      } elseif (method_exists($exception, 'getStatusCode')) {
        $statusCode = $exception->getStatusCode();
      } else {
        $statusCode = 500;
      }
    }

    $response['status'] = $statusCode;

    $response['error'] = $message ? $message : $exception->getMessage();
    if (!$response['error']) {
      switch ($statusCode) {
        case 401:
          $response['error'] = 'Unauthorized';
          break;
        case 403:
          $response['error'] = 'Forbidden';
          break;
        case 404:
          $response['error'] = 'Not Found';
          break;
        case 405:
          $response['error'] = 'Method Not Allowed';
          break;
        case 422:
          $response['error'] = $exception->original['message'];
          $response['errors'] = $exception->original['errors'];
          break;
        default:
          $response['error'] = ($statusCode == 500) ? 'Whoops, looks like something went wrong' : $exception->getMessage();
          break;
      }
    }

    // Log::info("customApiResponse: ", [$response, $exception->getMessage(), $exception->getCode()]);
    if (config('app.debug')) {
      $response['trace'] = $exception->getTrace();
    }

    return response()->json($response, $statusCode);
  }
}
