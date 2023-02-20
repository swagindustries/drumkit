<?php
/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace SwagIndustries\MercureRouter\Mercure;

use Amp\Pipeline\Queue;
use Symfony\Component\Uid\Uuid;

class Subscriber
{
    public readonly string $id;
    public readonly array $privateTopics;
    /** @var string[] */
    public readonly array $topics;
    public readonly array $payload;

    public readonly Queue $emitter;

    public function __construct(array $topics, array $privateTopics = [], array $payload = [])
    {
        $this->emitter = new Queue();
        $this->id = Uuid::v4();
        $this->topics = $topics;
        $this->privateTopics = $privateTopics;
        $this->payload = $payload;
    }

    public function dispatch(Update $update)
    {
        $this->emitter->pushAsync($update->format());
    }


//    public function readEvents(callable $emit)
//    {
//        while (true) {
//            if (empty($this->messages)) {
//                return;
//            }
//            foreach ($this->messages as $message) {
//                yield $emit($message->format());
//            }
//            $this->messages = [];
//        }
//    }
}
