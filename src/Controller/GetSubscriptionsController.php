<?php

namespace SwagIndustries\MercureRouter\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Status;
use Amp\Promise;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Mercure\Store\EventStoreInterface;
use SwagIndustries\MercureRouter\Mercure\Subscriber;
use function Amp\call;

class GetSubscriptionsController implements RequestHandler
{
    use SubscriptionNormalizerTrait;
    public function __construct(private Hub $mercure, private NotFoundController $notFound) {}
    public function handleRequest(Request $request): Promise
    {
        ['topic' => $topicQuery, 'subscriber' => $subscriberId] = $request->getAttribute(Router::class) + ['topic' => null, 'subscriber' => null];

        // Getting a single subscription
        if (!empty($topicQuery && !empty($subscriberId))) {
            $subscriber = $this->mercure->getSubscriber($subscriberId);
            if (empty($subscriber) || !in_array($topicQuery, $subscriber->topics)) {
                return $this->notFound->handleRequest($request);
            }

            $responseContent = ['@context' => 'https://mercure.rocks/'];
            $responseContent = array_merge($responseContent, $this->normalizeSubscription($subscriber, $topicQuery));

        // Getting a collection of subscriptions
        } else {
            $subscriptions = [];
            foreach ($this->mercure->getSubscribers() as $subscriber) {
                foreach ($subscriber->topics as $topic) {
                    if (!empty($topicQuery) && $topicQuery !== $topic) {
                        continue;
                    }

                    $subscriptions[] = $this->normalizeSubscription($subscriber, $topic);
                }
            }

            $responseContent = [
                '@context' => 'https://mercure.rocks/',
                'id' => Hub::MERCURE_PATH . '/subscriptions',
                'subscriptions' => $subscriptions,
                'type' => 'Subscriptions'
            ];
        }

        $responseContent['lastEventID'] = (string) $this->mercure->getLastEventID();

        return call(function () use ($responseContent) {
            return new Response(
                Status::OK,
                [
                    // TODO: fixme (security issue with *)
                    'Access-Control-Allow-Origin' => '*',
                    // Content-Type is a requirement from the spec
                    // "The web API MUST set the `Content-Type` HTTP header to `application/ld+json`."
                    // https://mercure.rocks/spec#subscription-api
                    'Content-Type' => 'application/ld+json',
                    'Cache-Control' => 'no-cache',
                    'X-Accel-Buffering' => 'no'
                ],
                json_encode($responseContent)
            );
        });
    }
}
