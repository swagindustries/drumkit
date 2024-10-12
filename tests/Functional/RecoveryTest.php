<?php

/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace SwagIndustries\MercureRouter\Test\Functional;

use PHPUnit\Framework\TestCase;

use SwagIndustries\MercureRouter\Test\Functional\AbstractFunctionalTest;
use SwagIndustries\MercureRouter\Test\Functional\Tool\TestClient;
use SwagIndustries\MercureRouter\Test\Functional\Tool\TestSubscriber;
use function Amp\async;
use function Amp\Future\await;

class RecoveryTest extends AbstractFunctionalTest
{
    public function testItCanRecoverySomeMessagesBefore(): void
    {
        $client = new TestClient();

        // 1. Send 3 updates
        $result1 = $client->sendUpdate([
            'id' => 'foo',
            'topic' => 'https://example.com/my-topic',
            'data' => ['message' => 'foo message'],
        ], isPrivate: true)->getBody()->buffer();

        $result2 = $client->sendUpdate([
            'id' => 'bar',
            'topic' => 'https://example.com/my-topic',
            'data' => ['message' => 'bar message'],
        ], isPrivate: true)->getBody()->buffer();

        $result3 = $client->sendUpdate([
            'id' => 'baz',
            'topic' => 'https://example.com/my-topic',
            'data' => ['message' => 'baz message'],
        ], isPrivate: true)->getBody()->buffer();

        $this->assertEquals('foo', $result1);
        $this->assertEquals('bar', $result2);
        $this->assertEquals( 'baz', $result3);

        // 2. Subscribe using "bar" id to simulate a reconnection
        $subscriber = new TestSubscriber(
            topic: 'https://example.com/my-private-topic',
        );

        $subscription = $subscriber->subscribe(lastEventId: 'bar');

        [,$hasReceivedBar, $hasReceivedBaz] = await([
            $subscription,
            $subscriber->received(['message' => 'bar message']),
            $subscriber->received(['message' => 'baz message']),
        ]);

        $this->assertTrue($hasReceivedBar);
        $this->assertTrue($hasReceivedBaz);
    }
}
