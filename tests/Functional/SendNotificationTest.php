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
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\FactoryTokenProvider;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class SendNotificationTest extends TestCase
{
    public function testItRespondToHttpRequest()
    {
        Loop::run(function () {

            // Listen
            $cookieJar = new InMemoryCookieJar();
            $hubUrl = 'https://localhost/.well-known/mercure?topic='.urlencode('https://example.com/books/1.jsonld');
            $token = (new LcobucciFactory('!ChangeMe!'))->create(['https://example.com/books/1.jsonld']);
            $cookieJar->store(new ResponseCookie('mercureAuthorization', $token, CookieAttributes::default()->withDomain('localhost')));
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

                // Output the results
                \printf(
                    "HTTP/%s %d %s\r\n%s\r\n\r\n",
                    $response->getProtocolVersion(),
                    $response->getStatus(),
                    $response->getReason(),
                    (string) $response->getRequest()->getUri()
                );

                foreach ($response->getHeaders() as $field => $values) {
                    foreach ($values as $value) {
                        print "$field: $value\r\n";
                    }
                }


                // The response body is an instance of Payload, which allows buffering or streaming by the consumers choice.
                // We could also use Amp\ByteStream\pipe() here, but we want to show some progress.
                while (null !== $chunk = yield $response->getBody()->read()) {
                    echo $chunk;
                    if (str_contains($chunk, 'Hi from the test suite')) {
                        Loop::stop();
                    }
                }
            });


            // Notify
            $body = new FormBody();
            $body->addField('topic', 'https://example.com/books/1.jsonld');
            $body->addField('data', 'Hi from the test suite');

            $notifierClient = HttpClientBuilder::buildDefault();
            $request = new Request('https://localhost/.well-known/mercure');
            $request->setBody($body);
            $request->setMethod('POST');
            $request->addHeader('Authorization', 'Bearer '.(new LcobucciFactory('!ChangeMe!'))->create());
            $promise = $notifierClient->request($request);
            $response = yield $promise;

            $result = yield $response->getBody()->buffer();

            echo $result;

        });

    }

    public function testItRespondToSymfonyRequests()
    {
        $hub = new Hub('https://localhost/.well-known/mercure', new FactoryTokenProvider(new LcobucciFactory('!ChangeMe!')));
        $id = $hub->publish(new Update('https://example.com/books/1.jsonld', 'Hi from Symfony!'));
        dump($id);
    }
}
