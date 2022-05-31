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

class Subscriber
{
    public readonly array $privateTopics;
    public readonly array $topics;
    private $connection;

    public function __construct(array $topics, array $privateTopics = [])
    {
        $this->topics = [];
        $this->privateTopics = [];
    }

    public function dispatch(Update $update): Promise
    {
        // TODO
    }
}
