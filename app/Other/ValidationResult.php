<?php

namespace App\Other;

/**
 * A class for storing validation results
 */
class ValidationResult {
  /**
   * Validation status
   *
   * @var boolean
   */
  protected $valid = true;

  /**
   * Validation message
   *
   * @var string
   */
  protected $message = "Validation has been successfully completed";

  /**
   * Validation status code
   *
   * @var integer
   */
  protected $code = 200;

  /**
   *
   * @param string|null $message
   * @param integer|string|null $code
   * @param bool $valid
   */
  public function __construct($message = null, $code = null, $valid = null)
  {
    if ($message) $this->message = $message;
    if (!is_null($code)) $this->code = (int) $code;
    if (!is_null($valid)) $this->valid = (bool) $valid;
  }

  /**
   * Set validation Error
   *
   * @param string $message
   * @param integer $code
   * @return ValidationResult
   */
  public function setError($message = "Validation Error", $code = 422)
  {
    $this->valid = false;
    $this->message = $message;
    $this->code = (int) $code;
    return $this;
  }

  /**
   * Get validation status
   *
   * @return boolean
   */
  public function isValid(): bool
  {
    return $this->valid;
  }

  /**
   * Get validation error status
   *
   * @return boolean
   */
  public function isError(): bool
  {
    return !$this->valid;
  }

  /**
   * Get validation message
   *
   * @return string
   */
  public function getMessage(): string
  {
    return $this->message;
  }

  /**
   * Get validation status code
   *
   * @return string
   */
  public function getCode(): int
  {
    return $this->code;
  }

  /**
   * Get a response with the validation result
   *
   * @return \Illuminate\Http\Response
   */
  public function getResponse()
  {
    if ($this->valid) {
      return response()->json($this->message, $this->code);
    } else {
      return response()->json(['error' => $this->message, 'status' => $this->code], $this->code);
    }
  }
}
