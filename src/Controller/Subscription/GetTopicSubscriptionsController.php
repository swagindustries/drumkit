<?php

namespace SwagIndustries\MercureRouter\Controller\Subscription;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use SwagIndustries\MercureRouter\Controller\NotFoundController;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Security\Security;

class GetTopicSubscriptionsController implements RequestHandler
{
    use SubscriptionNormalizerTrait;
    use SubscriptionApiResponseTrait;
    public function __construct(private Hub $mercure, private NotFoundController $notFound) {}
    public function handleRequest(Request $request): Response
    {
        ['topic' => $topicQuery] = $request->getAttribute(Router::class);

        /** @var array{subscribe: array|string|null, payload?: array} $jwtContent */
        $jwtContent = $request->getAttribute(Security::ATTRIBUTE_JWT_PAYLOAD)['mercure'] ?? [];
        $allowedTopics = (array) ($jwtContent['subscribe'] ?? []);

        $validPaths = [
            Hub::MERCURE_PATH . '/subscriptions{/topic}',
            Hub::MERCURE_PATH . '/subscriptions{/topic}{/subscriber}',
        ];

        if (empty(array_intersect($validPaths, $allowedTopics))) {
            return $this->forbiddenApiResponse();
        }

        $subscriptions = [];
        foreach ($this->mercure->getSubscribers() as $subscriber) {
            foreach ($subscriber->topics as $topic) {
                if ($topicQuery !== $topic) {
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

        return $this->getSubscriptionApiResponse($responseContent);
    }
}
