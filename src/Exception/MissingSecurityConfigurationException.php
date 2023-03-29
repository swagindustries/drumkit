<?php

namespace SwagIndustries\MercureRouter\Exception;

use JetBrains\PhpStorm\Pure;

class MissingSecurityConfigurationException extends \DomainException implements ExceptionInterface
{
    #[Pure] public function __construct()
    {
        parent::__construct('No security option are defined. The server cannot start without it.');
    }
}
