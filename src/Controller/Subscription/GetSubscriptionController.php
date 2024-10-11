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
use Amp\Http\Server\Router;
use SwagIndustries\MercureRouter\Controller\NotFoundController;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Security\Security;

class GetSubscriptionController implements RequestHandler
{
    use SubscriptionNormalizerTrait;
    use SubscriptionApiResponseTrait;
    public function __construct(private Hub $mercure, private NotFoundController $notFound) {}
    public function handleRequest(Request $request): Response
    {
        ['topic' => $topicQuery, 'subscriber' => $subscriberId] = $request->getAttribute(Router::class);

        /** @var array{subscribe: array|string|null, payload?: array} $jwtContent */
        $jwtContent = $request->getAttribute(Security::ATTRIBUTE_JWT_PAYLOAD)['mercure'] ?? [];
        $allowedTopics = (array) ($jwtContent['subscribe'] ?? []);

        $validPaths = [
            Hub::MERCURE_PATH . '/subscriptions{/topic}{/subscriber}',
        ];
        dump($allowedTopics);
        dump($validPaths);
        dump(array_intersect($validPaths, $allowedTopics));
        if (empty(array_intersect($validPaths, $allowedTopics))) {
            return $this->forbiddenApiResponse();
        }

        // Getting a single subscription
        $subscriber = $this->mercure->getSubscriber($subscriberId);
        if (empty($subscriber) || !in_array($topicQuery, $subscriber->topics)) {
            return $this->notFound->handleRequest($request);
        }

        $responseContent = ['@context' => 'https://mercure.rocks/'];
        $responseContent = array_merge($responseContent, $this->normalizeSubscription($subscriber, $topicQuery));

        return $this->getSubscriptionApiResponse($responseContent);
    }
}
