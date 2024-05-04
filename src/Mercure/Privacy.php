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

final class Privacy
{
    public function subscriberCanReceive(Subscriber $subscriber, Update $update): bool
    {
        if ($update->private) {
            return
                !empty(array_intersect($subscriber->privateTopics, $update->topics))
                && (
                    !empty(array_intersect($subscriber->topics, $update->topics))
                    || in_array('*', $subscriber->topics, true)
                );
        }

        if (in_array('*', $subscriber->topics, true)) {
            return true;
        }

        return !empty(array_intersect($subscriber->topics, $update->topics));
    }
}
