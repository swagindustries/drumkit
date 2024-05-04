<?php

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
}
