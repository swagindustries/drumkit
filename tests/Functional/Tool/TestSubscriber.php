<?php

namespace SwagIndustries\MercureRouter\Test\Functional\Tool;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\Future;
use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Pipeline\DisposedException;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use function Amp\async;
use function Amp\delay;

class TestSubscriber
{
    public const PASSPHRASE_JWT = '!ChangeThisMercureHubJWTSecretKey!';
    private HttpClient $client;
    private string $buffer;
    private Response|null $response = null;
    private $cancel;

    private float $timeout = 60;
    public function __construct(private string $topic)
    {
        $this->buffer = '';
        $tlsContext = (new ClientTlsContext(''))
            ->withoutPeerVerification();

        $connectContext = (new ConnectContext())
            ->withTlsContext($tlsContext);

        $this->client = (new HttpClientBuilder)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(null, $connectContext)))
            ->build();
    }

    public function setTimeout(float $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function subscribe(string $token = null): Future
    {
        if ($token === null) {
            $token = (new LcobucciFactory(self::PASSPHRASE_JWT))->create(['https://example.com/my-private-topic']);
        }

        return async(function () use($token) {
            $request = new Request('https://127.0.0.1/.well-known/mercure?topic='.urlencode($this->topic), 'GET');
            $request->addHeader('Authorization', 'Bearer '.$token);
            $request->setInactivityTimeout($this->timeout);
            $request->setTransferTimeout($this->timeout);
            $request->setTcpConnectTimeout($this->timeout);

            $this->cancel = new DeferredCancellation();
            $this->response = $this->client->request($request, $this->cancel->getCancellation());
            try {
                while (null !== $chunk = $this->response->getBody()->read()) {
                    $this->buffer .= $chunk;
                }
            } catch (CancelledException) {

            }
        });
    }

    public function stop(): void
    {
        $this->cancel->cancel();
    }

    /**
     * @return Future<bool>
     */
    public function received(array $data): Future
    {
        $data = json_encode($data, flags: JSON_THROW_ON_ERROR);

        return async(function () use ($data) {
            for ($i = 0; $i < 10; $i++) {
                if (str_contains($this->buffer, $data)) {
                    $this->stop();
                    return true;
                }

                delay(0.1); // Wait for 1 second for a message to be received
            }

            $this->stop();
            return false;
        });
    }

    /**
     * @return Future<bool>
     */
    public function receivedNothing(float $waitingTime = 1): Future
    {
        return async(function () use ($waitingTime) {
            for ($i = 0; $i < $waitingTime*10; $i++) {
                if (!empty(trim($this->buffer))) {
                    $this->stop();

                    return false;
                }

                delay(0.1);
            }

            $this->stop();
            return true;
        });
    }
}
