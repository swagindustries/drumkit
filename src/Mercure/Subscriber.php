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

use Amp\Deferred;
use Amp\Emitter;
use Amp\Iterator;
use Amp\Promise;
use Amp\Success;

class Subscriber
{
    public readonly array $privateTopics;
    public readonly array $topics;
    public readonly Emitter $emitter;

    /** @var Update[] */
    private array $messages;

    private $connection;

    public function __construct(array $topics, array $privateTopics = [])
    {
        $this->emitter = new Emitter();
        $this->topics = [];
        $this->privateTopics = [];
        $this->messages = [];
    }

    public function dispatch(Update $update): Promise
    {
        return $this->emitter->emit($update->format());
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
