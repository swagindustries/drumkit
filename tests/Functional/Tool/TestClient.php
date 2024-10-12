<?php

/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace SwagIndustries\MercureRouter\Test\Functional\Tool;

use Amp\Future;
use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\Form;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Nekland\Tools\StringTools;
use SwagIndustries\MercureRouter\Test\Functional\AbstractFunctionalTest;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use function Amp\async;
use function Amp\delay;

class TestClient
{
    public const PASSPHRASE_JWT = '!ChangeThisMercureHubJWTSecretKey!';
    private HttpClient $client;
    public function __construct()
    {
        $tlsContext = (new ClientTlsContext(''))
            ->withoutPeerVerification();

        $connectContext = (new ConnectContext())
            ->withTlsContext($tlsContext);

        $this->client = (new HttpClientBuilder)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(null, $connectContext)))
            ->build();
    }

    public function sendUpdate(array $data, bool $isPrivate = false, string $token = null): Response
    {
        $body = new Form();
        $body->addField('topic', $data['topic']);
        if (isset($data['id'])) {
            $body->addField('id', $data['id']);
        }

        $eventContent = $data['data'];
        if (is_array($eventContent)) {
            $eventContent = json_encode($eventContent, flags: JSON_THROW_ON_ERROR);
        }
        $body->addField('data', $eventContent);

        if ($isPrivate) {
            $body->addField('private','on');
        }

        if (empty($token)) {
            $token = (new LcobucciFactory(self::PASSPHRASE_JWT))->create();
        }

        $request = new Request('https://127.0.0.1:'.AbstractFunctionalTest::TLS_PORT.'/.well-known/mercure', 'POST', $body);
        $request->addHeader('Authorization', 'Bearer '.$token);
        $response = $this->client->request($request);

        return $response;
    }

    /**
     * @param callable(string, Response=): bool $expectation
     * @return Future<array{0: Response, 1: string}>
     */
    public function get(string $url, callable $expectation, ?string $token = null): Future
    {
        return async(function () use ($url, $expectation, $token) {
            $timeout = 0;
            do {
                if (str_contains($url, '/.well-known/mercure')) {
                    $url = StringTools::removeStart($url, '/.well-known/mercure');
                }

                $request = new Request('https://127.0.0.1:'.AbstractFunctionalTest::TLS_PORT.'/.well-known/mercure'. $url, 'GET');
                if ($token) {
                    $request->addHeader('Authorization', 'Bearer '.$token);
                }

                $response = $this->client->request($request);
                $content = $response->getBody()->buffer();
                if ($expectation($content, $response)) {
                    return [$response, $content];
                }
                delay(0.1);
                $timeout += 100;

            } while($timeout < 1000);

            return [$response, $content];
        });
    }
}
