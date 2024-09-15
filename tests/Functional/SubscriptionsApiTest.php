<?php

namespace Functional;

use Amp\Http\Client\Response;
use PHPUnit\Framework\TestCase;
use SwagIndustries\MercureRouter\Test\Functional\Tool\TestClient;
use SwagIndustries\MercureRouter\Test\Functional\Tool\TestSubscriber;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

class SubscriptionsApiTest extends TestCase
{
    public function testSubscriptionsList(): void
    {
        $topic = 'https://example.com/my-topic';
        $subscriber = new TestSubscriber(
            topic: $topic,
        );

        $subscription = $subscriber->subscribe();
        $client = new TestClient();

        /** @var Response $response */
        /** @var string $content */
        [,[$response, $content]] = await([
            $subscription,
            async(function () use ($client, $subscriber) {
                // Let some time pass for the subscription to be established
                $res = $client->get('/subscriptions', function (string $content) {
                    $content = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

                    return isset($content['subscriptions']) && $content['subscriptions'] > 1;
                })->await();
                $subscriber->stop();

                return $res;
            })
        ]);

        $content = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue(count($content['subscriptions']) > 0);
        $this->assertTrue($content['subscriptions'][0]['topic'] === $topic);
        $this->assertEquals($response->getHeader('Content-Type'), 'application/ld+json');
    }

    public function testSubscriptionsListByTopic(): void
    {
        $subscriber1 = new TestSubscriber(
            topic: 'https://example.com/my-topic',
        );
        $subscriber2 = new TestSubscriber(
            topic: 'https://example.com/my-other-topic',
        );

        $client = new TestClient();

        /** @var Response $response */
        /** @var string $content */
        [,,[$response, $content]] = await([
            $subscriber1->subscribe(),
            $subscriber2->subscribe(),
            async(function () use ($client, $subscriber1, $subscriber2) {
                // Let some time pass for the subscription to be established
                $res = $client->get('/subscriptions/'.urlencode('https://example.com/my-other-topic'), function (string $content) {
                    $content = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

                    return isset($content['subscriptions']) && $content['subscriptions'] > 1;
                })->await();
                $subscriber1->stop();
                $subscriber2->stop();

                return $res;
            })
        ]);

        $content = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue(count($content['subscriptions']) > 0);
        $this->assertTrue($content['subscriptions'][0]['topic'] === 'https://example.com/my-other-topic');
    }

    public function testGetASpecificSubscription(): void
    {
        $subscriber1 = new TestSubscriber(
            topic: 'https://example.com/my-topic',
        );

        $client = new TestClient();

        /** @var Response $response */
        /** @var string $content */
        [,[$response, $content]] = await([
            $subscriber1->subscribe(),
            async(function () use ($client, $subscriber1) {
                $topic = urlencode('https://example.com/my-topic');
                // Let some time pass for the subscription to be established
                $res = $client->get('/subscriptions/'.$topic, function (string $content) {
                    $content = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

                    return isset($content['subscriptions']) && $content['subscriptions'] > 1;
                })->await();

                $response = json_decode($res[1], true, flags: JSON_THROW_ON_ERROR);
                if (!empty($response['subscriptions'])) {
                    $subscriptionId = $response['subscriptions'][0]['id'];
                    $res = $client->get($subscriptionId, function (string $content) {
                        $content = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

                        return isset($content['type']) && $content['type'] === 'Subscription';
                    })->await();
                }

                $subscriber1->stop();

                return $res;
            })
        ]);

        $content = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        $this->assertEquals($response->getRequest()->getUri()->getPath(), $content['id']);
        $this->assertTrue($content['active']);
        $this->assertEquals('https://example.com/my-topic', $content['topic']);
        $this->assertEquals('Subscription', $content['type']);
    }

    public function testItReturns404ErrorIfSubscriptionNotFound(): void
    {
        $client = new TestClient();

        [$response, $content] = $client->get(
            '/subscriptions/topic/non-existing-subscription',
            fn($content, Response $response): bool => $response->getStatus() === 404
        )->await();

        $this->assertEquals(404, $response->getStatus());
    }

    public function testICannotAccessSubscriptionListWithoutCorrectCredentials(): void
    {
        $topic = 'https://example.com/my-topic';
        $subscriber = new TestSubscriber(
            topic: $topic,
        );

        $subscription = $subscriber->subscribe();
        $client = new TestClient();

        /** @var Response $response */
        /** @var string $content */
        [,[$response, $content]] = await([
            $subscription,
            async(function () use ($client, $subscriber) {
                // Let some time pass for the subscription to be established
                $res = $client->get('/subscriptions', function (string $content) {
                    $content = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

                    return isset($content['subscriptions']) && $content['subscriptions'] > 1;
                })->await();
                $subscriber->stop();

                return $res;
            })
        ]);

        $content = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue(count($content['subscriptions']) > 0);
        $this->assertTrue($content['subscriptions'][0]['topic'] === $topic);
    }
}
