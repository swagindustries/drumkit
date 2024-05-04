<?php

namespace SwagIndustries\MercureRouter\Test\Functional\Tool;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\Form;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;

class TestClient
{
    private const PASSPHRASE_JWT = '!ChangeThisMercureHubJWTSecretKey!';
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

    public function sendUpdate(array $data, bool $isPrivate = false): int
    {
        $body = new Form();
        $body->addField('topic', $data['topic']);

        $eventContent=$data['data'];
        if (is_array($eventContent)) {
            $eventContent = json_encode($eventContent, flags: JSON_THROW_ON_ERROR);
        }
        $body->addField('data', $eventContent);

        if ($isPrivate) {
            $body->addField('private','on');
        }

        $request = new Request('https://127.0.0.1/.well-known/mercure', 'POST', $body);
        $request->addHeader('Authorization', 'Bearer '.(new LcobucciFactory(self::PASSPHRASE_JWT))->create());
        $response = $this->client->request($request);

        return $response->getStatus();
    }
}
