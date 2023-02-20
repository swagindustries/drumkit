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
use Amp\ByteStream\ReadableIterableStream;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SwagIndustries\MercureRouter\Http\QueryParser;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Mercure\Subscriber;
use SwagIndustries\MercureRouter\Mercure\Update;
use SwagIndustries\MercureRouter\Security\Security;
use Symfony\Component\Uid\Uuid;
use function Amp\call;

class SubscribeController implements RequestHandler
{
    use SubscriptionNormalizerTrait;
    public function __construct(
        private Hub $mercure,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function handleRequest(Request $request): Response
    {
        /** @var array{topic?: array|string} $query */
        $query = QueryParser::parse($request->getUri()->getQuery());

        /** @var array $jwtContent */
        $jwtContent = $request->getAttribute(Security::ATTRIBUTE_JWT_PAYLOAD)['mercure'] ?? [];

        $subscriber = new Subscriber(
            (array) $query['topic'],
            $this->validateAndReturnTopics((array) ($jwtContent['subscribe'] ?? [])),
            (array) ($jwtContent['payload'] ?? [])
        );

        $this->logger->debug("New subscriber with query '{$request->getUri()->getQuery()}'");

        $this->mercure->addSubscriber($subscriber);
        $this->publishSubscriptions($subscriber, true);

//        $request->getClient()->onClose(function () use ($subscriber) {
//            $this->mercure->removeSubscriber($subscriber);
//            $this->publishSubscriptions($subscriber, false);
//            $subscriber->emitter->complete();
//        });

        $response = new Response(HttpStatus::OK,
            [
                // TODO: fixme (security issue with *)
                'Access-Control-Allow-Origin' => '*',
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no'
            ],
            new ReadableIterableStream($subscriber->emitter->iterate())
        );
        $response->onDispose(function () use ($subscriber) {
            $this->mercure->removeSubscriber($subscriber);
            $this->publishSubscriptions($subscriber, false);
            $subscriber->emitter->complete();
        });

        return $response;
    }
    private function publishSubscriptions(Subscriber $subscriber, bool $active): void
    {
        foreach ($subscriber->topics as $topic) {
            $this->mercure->publish(new Update(
                topics: ['/.well-known/mercure/subscriptions{/topic}{/subscriber}'],
                data: json_encode(array_merge(
                    ['@context' => 'https://mercure.rocks/'],
                    $this->normalizeSubscription($subscriber, $topic, $active)
                )),
                private: true,
                id: (string)Uuid::v4(),
                type: null
            ));
        }
    }

    private function validateAndReturnTopics(array $subscribe): array
    {
        $topics = [];
        foreach ($subscribe as $topic) {
            if (!is_string($topic)) {
                continue;
            }

            $topics[] = $topic;
        }

        return $topics;
    }
}
