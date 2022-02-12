<?php

namespace SwagIndustries\MercureRouter\Controller;

use Amp\Http\Server\FormParser\BufferingParser;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use SwagIndustries\MercureRouter\Mercure\Hub;

class ProducerController implements ControllerInterface
{
    public function __construct(
        private string $mercurePath = '/.well-known/mercure',
        private Hub $mercure,
        private ResponseMode $mode = ResponseMode::NORMAL,
    ) {}

    public function support(Request $request): bool
    {
        return $request->getUri()->getPath() === $this->mercurePath && $request->getMethod() === 'GET';
    }

    public function resolve(Request $request): Response
    {
        // Validation of the publication
        // see https://mercure.rocks/spec#publication
        $contentType = $request->getHeader('Content-Type');
        if ($contentType !== 'application/x-www-form-urlencoded') {

            return $this->respond('Wrong content type');
        }

        $parser = new BufferingParser();
        $form = $parser->parseForm($request);

        // TODO

        return new Response(Status::OK, [
            'Content-Type' => 'text/plain',
        ], $id);
    }

    private function respond(string $message): Response
    {
        if ($this->mode === ResponseMode::VERBOSE) {
            return new Response(Status::BAD_REQUEST, [
                'Content-Type' => 'plain/text'
            ], $message);
        }

        return new Response(Status::BAD_REQUEST);
    }
}
