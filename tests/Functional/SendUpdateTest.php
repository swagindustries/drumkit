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

use SwagIndustries\MercureRouter\Test\Functional\Tool\TestClient;
use PHPUnit\Framework\TestCase;
use SwagIndustries\MercureRouter\Test\Functional\Tool\TestSubscriber;
use function Amp\async;
use function Amp\Future\await;

class SendUpdateTest extends TestCase
{
    public function testSendUpdate(): void
    {
        $subscriber = new TestSubscriber(
            topic: 'https://example.com/my-private-topic',
        );

        $subscription = $subscriber->subscribe();


        [,,$hasReceivedMessage] = await([
            $subscription,
            async(function () {
                $client = new TestClient();

                $client->sendUpdate([
                    'topic' => 'https://example.com/my-private-topic',
                    'data' => [
                        'message' => 'Hello, World!',
                    ],
                ]);
            }),
            $subscriber->received(['message' => 'Hello, World!']),
        ]);

        $this->assertTrue($hasReceivedMessage);
    }

    public function testSubscribeOnAnything()
    {
        $subscriber = new TestSubscriber(
            topic: '*',
        );

        $subscription = $subscriber->subscribe();

        [,,$hasReceivedFirstMessage,$hasReceivedSecondMessage,$hasReceivedThirdMessage] = await([
            $subscription,
            async(function () {
                $client = new TestClient();

                $client->sendUpdate([
                    'topic' => 'https://example.com/books/1.jsonld',
                    'data' => ['message' => 'Event on book 1'],
                ]);

                $client->sendUpdate([
                    'topic' => 'https://example.com/books/2.jsonld',
                    'data' => ['message' => 'Event on book 2'],
                ]);

                $client->sendUpdate([
                    'topic' => 'https://example.com/books/3.jsonld',
                    'data' => ['message' => 'Event on book 3'],
                ], true);
            }),
            $subscriber->received(['message' => 'Event on book 1']),
            $subscriber->received(['message' => 'Event on book 2']),
            $subscriber->received(['message' => 'Event on book 3']),
        ]);

        $this->assertTrue($hasReceivedFirstMessage);
        $this->assertTrue($hasReceivedSecondMessage);
        $this->assertFalse($hasReceivedThirdMessage);
    }

    public function testIDoNotReceiveUpdateIfOutOfScopeImListening(): void
    {
        $subscriber = new TestSubscriber(
            topic: 'https://example.com/my-private-topic',
        );

        $subscription = $subscriber->subscribe();

        [,,$receivedNothing] = await([
            $subscription,
            async(function () {
                $client = new TestClient();

                $client->sendUpdate([
                    'topic' => 'https://example.com/anotherTopic',
                    'data' => [
                        'message' => 'Hello, World!',
                    ],
                ]);
            }),
            $subscriber->receivedNothing(),
        ]);

        $this->assertTrue($receivedNothing);
    }

    public function testICanReceiveUpdateIfTheTokenAllowsIt()
    {
        $subscriber = new TestSubscriber(
            topic: 'https://example.com/my-private-topic',
        );

        $subscription = $subscriber->subscribe();

        [,,$receivedNothing] = await([
            $subscription,
            async(function () {
                $client = new TestClient();

                $client->sendUpdate([
                    'topic' => 'https://example.com/my-private-topic',
                    'data' => ['message' => 'Hello, World!'],
                ], isPrivate: true);
            }),
            $subscriber->received(['message' => 'Hello, World!']),
        ]);

        $this->assertTrue($receivedNothing);
    }
}
