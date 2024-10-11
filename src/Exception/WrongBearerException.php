<?php
/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

declare(strict_types=1);

namespace SwagIndustries\MercureRouter\Exception;

use JetBrains\PhpStorm\Pure;

class WrongBearerException extends RuntimeException
{
    #[Pure] public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Wrong bearer', 0, $previous);
    }
}
