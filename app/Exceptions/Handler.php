<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Log;
use SebastianBergmann\CodeCoverage\Util\DirectoryCouldNotBeCreatedException;
use Str;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

use function PHPSTORM_META\type;

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
      return $this->customApiResponse($e, $this->getErrorMessage($e, 'Bad request'), 400);
    }

    if ($e instanceof ModelNotFoundException) {
      return $this->customApiResponse($e, $this->getErrorMessage($e, 'Record not found'), 404);
    }
    if ($e instanceof RecordsNotFoundException) {
      return $this->customApiResponse($e, $this->getErrorMessage($e, 'Records not found'), 404);
    }

    if ($e instanceof CannotWriteFileException) {
      return $this->customApiResponse($e, $this->getErrorMessage($e, 'Failed to write the file'), 500);
    }
    if ($e instanceof DirectoryCouldNotBeCreatedException) {
      return $this->customApiResponse($e, $this->getErrorMessage($e, 'Failed to create a directory'), 500);
    }

    if ($e instanceof ValidationException) {
      return $this->customApiResponse($e, $this->getValidationErrorMessage($e, 'Invalid argument value'), 422);
    }

    return $this->customApiResponse($e);
  }

  /**
   * Creates an exception response
   *
   * @param Throwable|Exception|ValidationException|HttpException $exception
   * @param string $message
   * @param integer $statusCode
   * @return \Illuminate\Http\Response
   */
  private function customApiResponse($exception, $message = "", $statusCode = 0)
  {
    // Log::info("Handler->customApiResponse()", [$message, $statusCode, $exception]);
    if (!$statusCode) {
      $statusCode = $this->getStatusCode($exception);
    }
    $response['status'] = $statusCode;

    if ($message && gettype($message) === 'string' && strlen($message)) {
      $response['error'] = $message;
    } else {
      $response['error'] = $this->getErrorMessage($exception);
    }

    if (!$response['error']) {
      $response['error'] = $this->generateMessageByCode($statusCode);
    }

    Log::info("customApiResponse: ", [$response]);
    if (config('app.debug')) {
      $response['trace'] = $exception->getTrace();
    }

    return response()->json($response, $statusCode);
  }

  /**
   * Get Exception status code
   *
   * @param Throwable|Exception|ValidationException|HttpException $exception
   * @param integer $defaultCode
   * @return integer
   */
  private function getStatusCode($exception, $defaultCode = 500)
  {
    if (property_exists($exception, 'status') && $this->checkStatus($exception->status)) {
      return $exception->status;
    }
    if (method_exists($exception, 'getCode') && $this->checkStatus($exception->getCode())) {
      return $exception->getCode();
    }
    if (method_exists($exception, 'getStatusCode') && $this->checkStatus($exception->getStatusCode())) {
      return $exception->getStatusCode();
    }

    return $defaultCode;
  }

  /**
   * Checks the correctness of the exception status code
   *
   * @param integer $status
   * @return bool
   */
  private function checkStatus($status)
  {
    if ($status && is_numeric($status) && $status >= 200 && $status <= 600) return true;
    else return false;
  }

  /**
   * Get exception message
   *
   * @param Throwable|Exception|ValidationException|HttpException $exception
   * @param string $defaultMessage
   * @return string
   */
  private function getErrorMessage($exception,  $defaultMessage = '') {
    $message = $exception->getMessage();
    if ($message && gettype($message) === 'string' && strlen($message)) {
      return $message;
    }
    return $defaultMessage;
  }

  /**
   * Get ValidationException first error message
   *
   * @param Throwable|Exception|ValidationException|HttpException $exception
   * @param string $defaultMessage
   * @return string
   */
  private function getValidationErrorMessage($exception, $defaultMessage = 'Invalid argument value') {
    if (method_exists($exception, 'errors')) {
      $errors = $exception->errors();
      if ($errors && reset($errors)) {
        return reset($errors)[0];
      }
    }
    return $defaultMessage;
  }

  /**
   * Generates an error message based on the status code
   *
   * @param integer $statusCode
   * @return string
   */
  private function generateMessageByCode($statusCode) {
    switch ($statusCode) {
        case 401:
          return 'Unauthorized';
        case 403:
          return  'Forbidden';
        case 404:
          return 'Not Found';
        case 405:
          return 'Method Not Allowed';
        case 422:
          return 'Invalid argument value';
        default:
          return 'Whoops, looks like something went wrong';
      }
  }
}