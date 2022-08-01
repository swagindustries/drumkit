<?php

namespace SwagIndustries\MercureRouter\Exception;

use JetBrains\PhpStorm\Pure;

class BearerNotFoundException extends RuntimeException
{
    public function __construct(string $message = "Cannot found any bearer in the current request", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
