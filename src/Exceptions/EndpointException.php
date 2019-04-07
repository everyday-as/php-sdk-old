<?php

namespace GmodStore\API\Exceptions;

use Exception;
use Throwable;

class EndpointException extends Exception
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct('Endpoint Error: '.$message, $code, $previous);
    }
}
