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

use Amp\Http\Server\FormParser\BufferingParser;
use Amp\Http\Server\FormParser\Form;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Mercure\Update;
use Symfony\Component\Uid\Uuid;
use function Amp\call;
use function Amp\Http\Server\FormParser\parseForm;

class ProducerController implements ControllerInterface
{
    public function __construct(
        private Hub $mercure,
        private ResponseMode $mode = ResponseMode::NORMAL,
        private string $mercurePath = Hub::MERCURE_PATH,
    ) {}

    public function support(Request $request): bool
    {
        return $request->getUri()->getPath() === $this->mercurePath && $request->getMethod() === 'POST';
    }

    public function resolve(Request $request): Promise
    {
        // Validation of the publication
        // see https://mercure.rocks/spec#publication
        $contentType = $request->getHeader('Content-Type');
        if (!str_contains($contentType, 'application/x-www-form-urlencoded')) {

            return new Success($this->respond('Wrong content type'));
        }

        // TODO: ignore parameters containing wrong values
        return call(function () use ($request) {

            /** @var Form $form */
            $form = yield parseForm($request);

            $id = $form->getValue('id') ?? Uuid::v4()->toRfc4122();

            if (null !== $retry = $form->getValue('retry')) {
                if (!ctype_digit($retry)) {
                    return $this->respond('Wrong value for "retry" parameter');
                }
                $retry = (int) $retry;
            }

            $type = $form->getValue('type');
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

            yield $this->mercure->publish($update);

            return new Response(Status::OK, [
                'Content-Type' => 'text/plain',
            ], $id);
        });
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
