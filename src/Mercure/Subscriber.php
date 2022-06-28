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

use Amp\Promise;
use Amp\Success;

class Subscriber
{
    public readonly array $privateTopics;
    public readonly array $topics;

    /** @var Update[] */
    private array $messages;

    private $connection;

    public function __construct(array $topics, array $privateTopics = [])
    {
        $this->topics = [];
        $this->privateTopics = [];
        $this->messages = [];
    }

    public function dispatch(Update $update): Promise
    {
        $this->messages[] = $update;
    }

    public function readEvents(callable $emit)
    {
        while (true) {
            if (empty($this->messages)) {
                yield new Success(null);
            }
            foreach ($this->messages as $message) {
                yield $emit($message->format());
            }
            $this->messages = [];
        }
    }
}
