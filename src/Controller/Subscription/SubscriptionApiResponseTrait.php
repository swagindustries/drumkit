<?php

namespace SwagIndustries\MercureRouter\Controller\Subscription;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Response;
use SwagIndustries\MercureRouter\Mercure\Hub;

trait SubscriptionApiResponseTrait
{
    private Hub $mercure;
    private function getSubscriptionApiResponse(array $responseContent): Response
    {
        $responseContent['lastEventID'] = (string) $this->mercure->getLastEventID();

        return new Response(
            HttpStatus::OK,
            $this->headers(),
            json_encode($responseContent)
        );
    }
    private function forbiddenApiResponse(): Response
    {
        return new Response(
            HttpStatus::FORBIDDEN,
            $this->headers(),
            json_encode(['message' => 'unauthorized'])
        );
    }

    private function headers(): array
    {
        return [
            // Content-Type is a requirement from the spec
            // "The web API MUST set the `Content-Type` HTTP header to `application/ld+json`."
            // https://mercure.rocks/spec#subscription-api
            'Content-Type' => 'application/ld+json',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no'
        ];
    }
}
