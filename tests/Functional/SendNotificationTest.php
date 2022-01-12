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

use Amp\Http\Client\Body\FormBody;
use Amp\Http\Client\Cookie\CookieInterceptor;
use Amp\Http\Client\Cookie\InMemoryCookieJar;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\Cookie\CookieAttributes;
use Amp\Http\Cookie\ResponseCookie;
use Amp\Loop;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\FactoryTokenProvider;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Update;
use Webmozart\Assert\Assert;

class SendNotificationTest extends TestCase
{
    private string $token;
    private string $hubUrl;
    private array $topics;
    private array $eventsToPush;
    private array $expectedEvents;
    private ?\Closure $onReceivedData = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = (new LcobucciFactory('!ChangeMe!'))->create(['https://example.com/books/1.jsonld']);
        $this->hubUrl = 'https://localhost/.well-known/mercure';
        $this->eventsToPush = [];
        $this->expectedEvents = [];
    }

    public function testItRespondToHttpRequest()
    {
        $this->listen('https://example.com/books/1.jsonld');
//        $this->onReceivedData(function ($data) {
//            if (str_contains($data, 'Hi from the test suite')) {
//                return true;
//            }
//
//            return false;
//        });
        $this->expectedEvents = [
            'Hi from the test suite'
        ];

        $this->push('Hi from the test suite');

        $this->startTestLoop();
    }

    public function testItRespondToSymfonyRequests()
    {
        $hub = new Hub('https://localhost/.well-known/mercure', new FactoryTokenProvider(new LcobucciFactory('!ChangeMe!')));
        $id = $hub->publish(new Update('https://example.com/books/1.jsonld', 'Hi from Symfony!'));
        dump($id);
    }

    private function onReceivedData(\Closure $closure)
    {
        $this->onReceivedData = $closure;
    }

    private function listen(string ...$topics)
    {
        $this->topics = $topics;
    }

    private function push(array|string $event)
    {
        $this->eventsToPush[] = $event;
    }

    private function startTestLoop()
    {
        Assert::count($this->topics, 1, 'Multiple topic not supported by this method yet');
        $topic = reset($this->topics);

        Loop::run(function () use ($topic) {
            // Listen
            $cookieJar = new InMemoryCookieJar();
            $hubUrl = $this->hubUrl . '?topic='.urlencode($topic);
            $cookieJar->store(new ResponseCookie('mercureAuthorization', $this->token, CookieAttributes::default()->withDomain('localhost')));
            $client = (new HttpClientBuilder())
                ->interceptNetwork(new CookieInterceptor($cookieJar))
                ->build()
            ;
            $request = new Request($hubUrl);
            $request->setTransferTimeout(2000);    // 2secs
            $request->setInactivityTimeout(2000); // 2secs
            // Make an asynchronous HTTP request
            $promise = $client->request($request);

            $promise->onResolve(function ($error, Response $response) {

                if ($error) {
                    var_dump($error);
                    echo "Unknown error\n";
                    return;
                }

                // uncomment to debug
                // Output the results
//                \printf(
//                    "HTTP/%s %d %s\r\n%s\r\n\r\n",
//                    $response->getProtocolVersion(),
//                    $response->getStatus(),
//                    $response->getReason(),
//                    (string) $response->getRequest()->getUri()
//                );

//                foreach ($response->getHeaders() as $field => $values) {
//                    foreach ($values as $value) {
//                        print "$field: $value\r\n";
//                    }
//                }


                // The response body is an instance of Payload, which allows buffering or streaming by the consumers choice.
                // We could also use Amp\ByteStream\pipe() here, but we want to show some progress.
                while (null !== $chunk = yield $response->getBody()->read()) {
//                    $result = $this->onReceivedData($chunk);
//                    if ($result) {
//                        $this->assertTrue(true);
//                        Loop::stop();
//                    }

                    foreach ($this->expectedEvents as $expectedEventIndex => $expectedEvent) {
                        if (str_contains($chunk, $expectedEvent)) {
                            $this->assertStringContainsString($expectedEvent, $chunk);
                            unset($this->expectedEvents[$expectedEventIndex]);
                            break;
                        }
                    }

                    if (empty($this->expectedEvents)) {
                        Loop::stop();
                    }
                }
            });


            foreach ($this->eventsToPush as $event) {
                if (is_array($event)) {
                    $event = json_encode($event, flags: JSON_THROW_ON_ERROR);
                }
                // Notify
                $body = new FormBody();
                $body->addField('topic', $topic);
                $body->addField('data', $event);

                $notifierClient = HttpClientBuilder::buildDefault();
                $request = new Request('https://localhost/.well-known/mercure');
                $request->setBody($body);
                $request->setMethod('POST');
                $request->addHeader('Authorization', 'Bearer '.(new LcobucciFactory('!ChangeMe!'))->create());
                $promise = $notifierClient->request($request);
                $response = yield $promise;

                $result = yield $response->getBody()->buffer();
//                echo $result;
            }

        });
    }
}
