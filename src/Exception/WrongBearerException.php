<?php

namespace SwagIndustries\MercureRouter\Exception;

use JetBrains\PhpStorm\Pure;

class WrongBearerException extends RuntimeException
{
    #[Pure] public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Wrong bearer', 0, $previous);
    }
}
