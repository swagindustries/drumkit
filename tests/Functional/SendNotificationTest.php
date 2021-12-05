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
        $postData = [
            'topic' => 'https://example.com/books/1.jsonld',
            'data' => 'Hi from the test suite',
        ];

//        dump($postData->toString());exit;
        $client = HttpClient::create();
        $response = $client->request('POST', 'https://localhost/.well-known/mercure', [
            'body' => $postData,
            'auth_bearer' => (new LcobucciFactory('!ChangeMe!'))->create()
        ]);
        dump($response->getContent(false));
        dump($response);

    }

    public function testItRespondToSymfonyRequests()
    {
        $hub = new Hub('https://localhost/.well-known/mercure', new FactoryTokenProvider(new LcobucciFactory('!ChangeMe!')));
        $id = $hub->publish(new Update('https://example.com/books/1.jsonld', 'Hi from Symfony!'));
        dump($id);
    }
}
