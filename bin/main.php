#!/usr/bin/env php
<?php

require_once __DIR__ .'/../vendor/autoload.php';

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket;
use Amp\Http\Server\Request;
use Monolog\Logger;
use Amp\ByteStream\IteratorStream;
use Amp\Producer;

// Run this script, then visit http://localhost:1337/ or https://localhost:1338/ in your browser.

function newEvent() {
    $id = mt_rand(1, 1000);
    return ['id' => $id, 'title' => 'title ' . $id, 'content' => 'content ' . $id];
}

$events = [
    newEvent(),
    newEvent(),
];

Amp\Loop::run(static function () use (&$events) {
    $cert = new Socket\Certificate(__DIR__ . '/../ssl/mercure-router.local.pem', __DIR__ . '/../ssl/mercure-router.local-key.pem');

    $context = (new Socket\BindContext)
        ->withTlsContext((new Socket\ServerTlsContext)->withDefaultCertificate($cert));

    $servers = [
        Socket\Server::listen("0.0.0.0:1337"),
        Socket\Server::listen("[::]:1337"),
        Socket\Server::listen("0.0.0.0:1338", $context),
        Socket\Server::listen("[::]:1338", $context),
    ];

    $logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
    $logHandler->setFormatter(new ConsoleFormatter);
    $logger = new Logger('server');
    $logger->pushHandler($logHandler);

    $server = new HttpServer($servers, \Amp\Http\Server\Middleware\stack(new CallableRequestHandler(static function (Request $request) use (&$events) {
        if ($request->getUri()->getPath() === '/') {
            return new Response(
                Status::OK,
                [
                    "content-type" => "text/html; charset=utf-8"
                ],
                <<<FRONT
                <!DOCTYPE html>
                <html lang="en">
                    <head>
                    <title>Yo</title>
                    </head>
                    <body>
                        <h1>Hello World!</h1>
                        <div id="news"></div>
                        <script>
                        //*
                        var news = document.getElementById('news');
                        const evtSource = new EventSource("/sse");
                        evtSource.addEventListener('news', function (event) {
                            news.innerHTML = news.innerHTML + "<p>"+event.data+"</p>";
                        });
                        //*/
                        </script>            
                    </body>            
                </html>
                FRONT
            );
        }

        if ($request->getUri()->getPath() === '/sse') {
            return new Response(
                Status::OK,
                [
                    'Access-Control-Allow-Origin' => '*',
                    'Content-Type' => 'text/event-stream',
                    'Cache-Control' => 'no-cache',
                    'X-Accel-Buffering' => 'no'
                ],
                new IteratorStream(new Producer(function (callable $emit) use (&$events) {
                        while(true) {
                            if (empty($events)) {
                                yield new \Amp\Delayed(10);
                            } else {
                                $data = json_encode(array_pop($events));
                                yield $emit(
                                    "event: news\ndata: $data\n\n"
                                );
                            }
                        }
                    }
                ))
            );
        }

        if ($request->getUri()->getPath() === '/newevent') {
            $events[] = newEvent();
            return new Response(Status::OK, ["content-type" => "text/plain; charset=utf-8"], 'OK');
        }

        return new Response(Status::NOT_FOUND, ["content-type" => "text/plain; charset=utf-8"], '404 Not found');

    }), new \Amp\Http\Server\Middleware\CompressionMiddleware(12, 1)), $logger/*, (new \Amp\Http\Server\Options())->withoutCompression()*/);

    yield $server->start();

    // Stop the server when SIGINT is received (this is technically optional, but it is best to call Server::stop()).
    Amp\Loop::onSignal(\SIGINT, static function (string $watcherId) use ($server) {
        Amp\Loop::cancel($watcherId);
        yield $server->stop();
    });
});
