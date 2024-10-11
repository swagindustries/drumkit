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

class MissingSecurityConfigurationException extends \DomainException implements ExceptionInterface
{
    #[Pure] public function __construct()
    {
        parent::__construct('No security option are defined. The server cannot start without it.');
    }
}
