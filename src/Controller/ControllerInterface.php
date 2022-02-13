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

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;

interface ControllerInterface
{
    public function support(Request $request): bool;
    public function resolve(Request $request): Promise;
}
