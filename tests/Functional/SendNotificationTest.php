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

namespace SwagIndustries\MercureRouter\Test\Functional;

use Amp\Http\Client\Body\FormBody;
use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\Cookie\CookieInterceptor;
use Amp\Http\Client\Cookie\InMemoryCookieJar;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\Cookie\CookieAttributes;
use Amp\Http\Cookie\ResponseCookie;
use Amp\Loop;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use SwagIndustries\MercureRouter\Test\Functional\Pusher\Event;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;

class SendNotificationTest extends TestCase
{
    private string $token;
    private string $hubUrl;
    private array $topics;
    /** @var array<Event> */
    private array $eventsToPush;
    private array $expectedEvents;
    private array $unexpectedEvents;
    private bool $isPrivate;
    private ?\Closure $onReceivedData = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = (new LcobucciFactory('!ChangeMe!'))->create(['https://example.com/books/1.jsonld']);
        $this->hubUrl = 'https://localhost/.well-known/mercure';
        $this->eventsToPush = [];
        $this->expectedEvents = [];
        $this->unexpectedEvents = [];
        $this->isPrivate = false;
    }

    public function testItRespondToHttpRequest()
    {
        $this->listen('https://example.com/books/1.jsonld');

        $this->expectedEvents = [
            'Hi from the test suite'
        ];

        $this->push(new Event('Hi from the test suite', 'https://example.com/books/1.jsonld'));

        $this->startTestLoop();
    }

    public function testItSupportsMultilineStringEvents()
    {
        $this->listen('https://example.com/books/1.jsonld');

        $this->expectedEvents = [
            "Hi\n\n\nit still works!"
        ];

        $this->push(new Event("Hi\n\n\nit still works!", 'https://example.com/books/1.jsonld'));

        $this->startTestLoop();
    }

    public function testItIsPossibleToListenOnAnyEvent()
    {
        $this->listen('*');

        $this->expectedEvents = [
            'Event on book 1',
            'Event on book 2',
        ];

        $this->push(new Event('Event on book 1', 'https://example.com/books/1.jsonld'));
        $this->push(new Event('Event on book 2', 'https://example.com/books/2.jsonld'));

        $this->startTestLoop();
    }

    public function testIdoNotReceiveEveryEventsIfIDoNotSubscribe()
    {
        $this->listen('https://example.com/books/1.jsonld');

        $this->expectedEvents = [
            'Event on book 1',
        ];

        $this->unexpectedEvents = [
            'Event on book 2',
        ];

        $this->push(new Event('Event on book 1', 'https://example.com/books/1.jsonld'));
        $this->push(new Event('Event on book 2', 'https://example.com/books/2.jsonld'));

        $this->startTestLoop();
    }

    public function testIListenOnManyTopics()
    {
        $this->listen(
            'https://example.com/books/1.jsonld',
            'https://example.com/books/2.jsonld',
            'https://example.com/goats',
        );
        $this->expectedEvents = [
            'Event on book 1',
            'Event on book 2',
            'Event on goats',
        ];

        $this->push(new Event('Event on book 1', 'https://example.com/books/1.jsonld'));
        $this->push(new Event('Event on book 2', 'https://example.com/books/2.jsonld'));
        $this->push(new Event('Event on goats', 'https://example.com/goats'));

        $this->startTestLoop();

    }

    public function testItReceivedPrivateNotificationsContainedInTheJwt()
    {
        $this->listen('https://example.com/books/1.jsonld');

        $this->expectedEvents = [
            'Event on book 1',
        ];

        $this->push(new Event('Event on book 1', 'https://example.com/books/1.jsonld'));

        $this->startTestLoop();
    }

    public function testItDoesNotReceivePrivateNotificationsNotContainedInTheJwt()
    {
        $this->listen('https://example.com/books/2.jsonld');

        $this->unexpectedEvents = ['Event on book 2'];
        $this->isPrivate = true;

        $this->push(new Event('Event on book 2', 'https://example.com/books/2.jsonld'));

        $this->startTestLoop();
    }

    private function onReceivedData(\Closure $closure)
    {
        $this->onReceivedData = $closure;
    }

    private function listen(string ...$topics)
    {
        $this->topics = $topics;
    }

    private function push(Event $event)
    {
        $this->eventsToPush[] = $event;
    }

    private function startTestLoop()
    {
        $catchedException = null;
        Loop::run(function () use (&$catchedException) {
            // Listen
            $cookieJar = new InMemoryCookieJar();
            $hubUrl = $this->hubUrl . '?';
            foreach ($this->topics as $topic) {
                if ($this->hubUrl[-1] !== '?') {
                    $this->hubUrl .= '&';
                }
                $hubUrl .= 'topic='.urlencode($topic);
            }

            $cookieJar->store(new ResponseCookie('mercureAuthorization', $this->token, CookieAttributes::default()->withDomain('localhost')));

            $tlsContext = (new ClientTlsContext(''))->withoutPeerVerification();
            $connectContext = (new ConnectContext())->withTlsContext($tlsContext);


            $client = (new HttpClientBuilder())
                ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: $connectContext)))
                ->interceptNetwork(new CookieInterceptor($cookieJar))
                ->build()
            ;
            $request = new Request($hubUrl);

            // The following hard limit timeout avoids any test case to take
            // too much time
            $request->setTransferTimeout(2000);    // 2secs
            $request->setInactivityTimeout(2000); // 2secs
            // Make an asynchronous HTTP request
            $promise = $client->request($request);

            $promise->onResolve(function ($error, ?Response $response) use (&$catchedException) {

                if ($error) {
                    if ($error instanceof \Throwable) {
                        $catchedException = $error;
                    } else {
                        var_dump($error);
                        echo "Unknown error\n";
                    }

                    Loop::stop();
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

                    foreach ($this->expectedEvents as $expectedEventIndex => $expectedEvent) {
                        if (str_contains($chunk, $expectedEvent)) {
                            $this->assertStringContainsString($expectedEvent, $chunk);
                            unset($this->expectedEvents[$expectedEventIndex]);
                            break;
                        }
                    }

                    foreach ($this->unexpectedEvents as $unexpectedEventIndex => $unexpectedEvent) {
                        if (str_contains($chunk, $unexpectedEvent)) {
                            $this->assertStringNotContainsString($unexpectedEvent, $chunk,'The event has been received');
                            unset($this->unexpectedEvents[$unexpectedEventIndex]);
                            break;
                        }
                    }

                    if (empty($this->expectedEvents) && empty($this->unexpectedEvents)) {
                        Loop::stop();
                    }
                }
            });

            Loop::delay(1500, function () {
                // See after the event loop for checks
                Loop::stop();
            });


            foreach ($this->eventsToPush as $event) {
                $eventContent = $event->content();
                if (is_array($eventContent)) {
                    $eventContent = json_encode($eventContent, flags: JSON_THROW_ON_ERROR);
                }
                // Notify
                $body = new FormBody();
                $body->addField('topic', $event->topic());
                $body->addField('data', $eventContent);
                if($this->isPrivate) {
                    $body->addField('private','on');
                }

                $notifierClient = (new HttpClientBuilder())
                    ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(connectContext: $connectContext)))
                    ->build()
                ;
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

        if ($catchedException !== null) {
            throw $catchedException;
        }

        foreach ($this->expectedEvents as $event) {
            $this->assertStringNotContainsString($event, '', 'The event has not been received');
        }

        foreach ($this->unexpectedEvents as $unexpectedEvent) {
            // Trigger an assertion to validate the test
            $this->assertTrue(true);
        }
    }
}
