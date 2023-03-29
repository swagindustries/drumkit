<?php

namespace SwagIndustries\MercureRouter\Exception;

class InvalidFileConfigurationException extends \DomainException implements ExceptionInterface
{
    public function __construct(\Exception $e)
    {
        parent::__construct('Invalid file configuration provided, error: ' . $e->getMessage(), previous: $e);
    }
}
