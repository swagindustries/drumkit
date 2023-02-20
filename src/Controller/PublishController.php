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

namespace SwagIndustries\MercureRouter\Controller;

use Amp\Http\HttpStatus;
use Amp\Http\Server\FormParser\Form;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Mercure\Update;
use Symfony\Component\Uid\Uuid;
use function Amp\Http\Server\FormParser\parseForm;

class PublishController implements RequestHandler
{
    public function __construct(
        private Hub $mercure,
        private ResponseMode $mode = ResponseMode::NORMAL,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function handleRequest(Request $request): Response
    {
        // Validation of the publication
        // see https://mercure.rocks/spec#publication
        $contentType = $request->getHeader('Content-Type');
        if (!str_contains($contentType, 'application/x-www-form-urlencoded')) {

            return $this->respond('Wrong content type');
        }

        // TODO: ignore parameters containing wrong values (this is the official server behavior)
        // (add validation but ignore errors instead of failing or weird behavior)

        $form = parseForm($request);

        $id = $this->getValue($form->getValue('id'), Uuid::v4()->toRfc4122());

        $retry = $this->getValue($form->getValue('retry'), null);
        if (null !== $retry) {
            if (!ctype_digit($retry)) {
                return $this->respond('Wrong value for "retry" parameter');
            }
            $retry = (int) $retry;
        }

        $type = $this->getValue($form->getValue('type'), null);
        if ($type !== null && str_contains("\n", $type)) {
            return $this->respond('Wrong value for "type" parameter');
        }

        $update = new Update(
            topics: $form->getValueArray('topic'),
            data: $form->getValue('data'),
            // "on" is recommended
            // but any value including empty string make it true
            private: $form->getValue('private') !== null,
            id: $id,
            type: $type,
            retry: $retry
        );

        $topics = implode(', ', $update->topics);
        $this->logger->debug("publish data '{$update->data}' to topics {$topics}");
        $this->mercure->publish($update);

        return new Response(HttpStatus::OK, [
            'Content-Type' => 'text/plain',
        ], $id);
    }

    /**
     * Basically remove empty string to replace it by the default value
     * this behavior is questionable but this is how work the go
     * implementation of mercure.
     */
    private function getValue(?string $value, ?string $defaultValue): ?string
    {
        if (null === $value || $value === '') {
            return $defaultValue;
        }

        return $value;
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
