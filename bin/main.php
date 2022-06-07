#!/usr/bin/env php
<?php

require_once __DIR__ .'/../vendor/autoload.php';

// Running the server directly is here for testing purpose and should be fixed by running a command app.
$server = new \SwagIndustries\MercureRouter\Server(
    new \SwagIndustries\MercureRouter\Configuration\Options(
        __DIR__.'/../ssl/mercure-router.local.pem',
        __DIR__.'/../ssl/mercure-router.local-key.pem',
        devMode: true
    )
);
$server->start();
