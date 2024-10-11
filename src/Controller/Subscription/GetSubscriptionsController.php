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

namespace SwagIndustries\MercureRouter\Controller\Subscription;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use SwagIndustries\MercureRouter\Controller\NotFoundController;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Security\Security;

class GetSubscriptionsController implements RequestHandler
{
    use SubscriptionNormalizerTrait;
    use SubscriptionApiResponseTrait;
    public function __construct(private Hub $mercure, private NotFoundController $notFound) {}
    public function handleRequest(Request $request): Response
    {
        /** @var array{subscribe: array|string|null, payload?: array} $jwtContent */
        $jwtContent = $request->getAttribute(Security::ATTRIBUTE_JWT_PAYLOAD)['mercure'] ?? [];
        $allowedTopics = (array) ($jwtContent['subscribe'] ?? []);

        $validPaths = [
            Hub::MERCURE_PATH . '/subscriptions',
            Hub::MERCURE_PATH . '/subscriptions{/topic}',
            Hub::MERCURE_PATH . '/subscriptions{/topic}{/subscriber}',
        ];
        if (empty(array_intersect($validPaths, $allowedTopics))) {
            return $this->forbiddenApiResponse();
        }

        $subscriptions = [];
        foreach ($this->mercure->getSubscribers() as $subscriber) {
            foreach ($subscriber->topics as $topic) {
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
