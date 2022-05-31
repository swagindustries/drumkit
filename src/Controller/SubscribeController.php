<?php
/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace SwagIndustries\MercureRouter\Controller;

use Amp\ByteStream\IteratorStream;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use SwagIndustries\MercureRouter\Http\QueryParser;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Mercure\Subscriber;
use function Amp\call;

class SubscribeController implements ControllerInterface
{
    public function __construct(
        private Hub $mercure,
        private string $mercurePath = Hub::MERCURE_PATH,
    ) {}

    public function support(Request $request): bool
    {
        return $request->getUri()->getPath() === $this->mercurePath && $request->getMethod() === 'GET';
    }

    public function resolve(Request $request): Promise
    {
        $query = QueryParser::parse($request->getUri()->getQuery());

        $subscriber = new Subscriber();

        return call(function () use ($subscriber) {
            return new Response(Status::OK,
                [
                    // TODO: fixme (security issue with *)
                    'Access-Control-Allow-Origin' => '*',
                    'Content-Type' => 'text/event-stream',
                    'Cache-Control' => 'no-cache',
                    'X-Accel-Buffering' => 'no'
                ],
                new IteratorStream(new Producer([$subscriber, 'readEvents']))
            );
        });
    }
}
