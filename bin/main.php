#!/usr/bin/env php
<?php

// Enable TCP Sockets and SSL
$server = new \Swoole\HTTP\Server("0.0.0.0", 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);

// Setup the location of SSL cert and key files
$server->set([

    // Setup SSL files
    'ssl_cert_file' => 'ssl/mercure-router.local.pem',
    'ssl_key_file' => 'ssl/mercure-router.local-key.pem',

    // Enable HTTP2 protocol
    'open_http2_protocol' => true,
]);


$server->on('Request', function(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
{
    $response->end('<h1>Hello World!</h1>');
});

$server->start();
