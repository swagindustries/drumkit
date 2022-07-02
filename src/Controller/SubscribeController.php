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
namespace SwagIndustries\MercureRouter\Controller;

use Amp\ByteStream\IteratorStream;
use Amp\Emitter;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Producer;
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
        /** @var array{topic?: array|string} $query */
        $query = QueryParser::parse($request->getUri()->getQuery());

        $subscriber = new Subscriber((array) $query['topic']);

        $this->mercure->addSubscriber($subscriber);

        return call(function () use ($subscriber) {
            return new Response(Status::OK,
                [
                    // TODO: fixme (security issue with *)
                    'Access-Control-Allow-Origin' => '*',
                    'Content-Type' => 'text/event-stream',
                    'Cache-Control' => 'no-cache',
                    'X-Accel-Buffering' => 'no'
                ],
                new IteratorStream($subscriber->emitter->iterate())
            );
        });
    }
}
