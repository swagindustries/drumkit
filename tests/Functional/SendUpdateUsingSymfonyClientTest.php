<?php

namespace SwagIndustries\MercureRouter\Test\Functional;

use PHPUnit\Framework\TestCase;
use SwagIndustries\MercureRouter\Test\Functional\Tool\TestClient;
use SwagIndustries\MercureRouter\Test\Functional\Tool\TestSubscriber;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Update;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

class SendUpdateUsingSymfonyClientTest extends TestCase
{
    public function testSendUpdateUsingSymfonyHttpClient(): void
    {
        $subscriber = new TestSubscriber(
            topic: 'https://example.com/books/1',
        );

        $subscription = $subscriber->subscribe();

        $httpClient = HttpClient::create(['verify_peer' => false, 'verify_host'=> false]);
        $token = (new LcobucciFactory(TestClient::PASSPHRASE_JWT))->create();
        $hub = new Hub(
            'https://127.0.0.1/.well-known/mercure',
            jwtProvider: new StaticTokenProvider($token),
            httpClient: $httpClient,
        );


        [,,$hasReceivedMessage] = await([
            $subscription,
            async(function () use ($hub) {
                delay(0.5); // Ensure we subscribe before sending the update
                $update = new Update(
                    'https://example.com/books/1',
                    json_encode(['status' => 'OutOfStock']),
                );

                $hub->publish($update);
            }),
            $subscriber->received(['status' => 'OutOfStock']),
        ]);

        $this->assertTrue($hasReceivedMessage);
    }

}
