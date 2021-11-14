#!/usr/bin/env php
<?php

require_once __DIR__ .'/../vendor/autoload.php';

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

function newEvent() {
    $id = mt_rand(1, 1000);
    return [['id' => $id, 'title' => 'title ' . $id, 'content' => 'content ' . $id]];
}

$events = [
    newEvent(),
];

$server->on('Request', function(\Swoole\Http\Request $request, \Swoole\Http\Response $response) use ($events) {
    $uri = $request->server['request_uri'];

    if ($uri === '/') {
        $response->end(<<<FRONT
        <!DOCTYPE html>
        <html>
            <head>
            <title>Yo</title>
            </head>
            <body>
                <h1>Hello World!</h1>
                <div id="news"></div>
                <script>
                var news = document.getElementById('news');
                const evtSource = new EventSource("/sse");
                evtSource.addEventListener('news', function (event) {
                    news.innerHTML = news.innerHTML + "<p>"+event.data+"</p>";
                });
                </script>            
            </body>            
        </html>
        FRONT);
    } else if ($uri === '/newevent') {
        // TODO
    } else if ($uri === '/sse') {
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Content-Type', 'text/event-stream');
        $response->header('Cache-Control', 'no-cache');
        $response->header('Connection', 'keep-alive');
        $response->header('X-Accel-Buffering', 'no');

        $event = new \Hhxsv5\SSE\Event(function () use ($events) {
            $news = array_pop($events); // Get news from database or service.
            if (empty($news)) {
                return false; // Return false if no new messages
            }
            $shouldStop = false; // Stop if something happens or to clear connection, browser will retry
            if ($shouldStop) {
                throw new \Hhxsv5\SSE\StopSSEException();
            }
            echo "news";
            return \json_encode($news);
            // return ['event' => 'ping', 'data' => 'ping data']; // Custom event temporarily: send ping event
            // return ['id' => uniqid(), 'data' => json_encode(compact('news'))]; // Custom event Id
        }, 'news');

        (new \Hhxsv5\SSE\SSESwoole($event, $request, $response))->start();
    } else {
        $response->status(404);
        $response->end('<h1>404 ERROR</h1>');
    }
});

$server->start();
