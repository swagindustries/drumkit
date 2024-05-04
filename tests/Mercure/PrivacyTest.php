<?php

namespace Mercure;

use PHPUnit\Framework\TestCase;
use SwagIndustries\MercureRouter\Mercure\Privacy;
use SwagIndustries\MercureRouter\Mercure\Subscriber;
use SwagIndustries\MercureRouter\Mercure\Update;

class PrivacyTest extends TestCase
{
    public function testItCanCheckIfSubscriberCanReceiveUpdate(): void
    {
        $privacy = new Privacy();
        $subscriber = new Subscriber(['topic1', 'topic2']);
        $update = new Update(['topic1'], 'data', false, 'id', 'type');
        $update2 = new Update(['topic2'], 'data', false, 'id', 'type');
        $update3 = new Update(['topic3'], 'data', false, 'id', 'type');

        $this->assertTrue($privacy->subscriberCanReceive($subscriber, $update));
        $this->assertTrue($privacy->subscriberCanReceive($subscriber, $update2));
        $this->assertFalse($privacy->subscriberCanReceive($subscriber, $update3));
    }

    public function testOneCanSubscribeToAll(): void
    {
        $privacy = new Privacy();
        $subscriber = new Subscriber(['*']);
        $update = new Update(['topic1'], 'data', false, 'id', null);
        $update2 = new Update(['topic2'], 'data', false, 'id', null);

        $this->assertTrue($privacy->subscriberCanReceive($subscriber, $update));
        $this->assertTrue($privacy->subscriberCanReceive($subscriber, $update2));
    }

    public function testIfTheUpdateIsPrivateTheSubscriberCannotReceiveIt(): void
    {
        $privacy = new Privacy();
        $subscriber = new Subscriber(['topic1', 'topic2'], ['topic2']);
        $update = new Update(['topic1'], 'data', true, 'id', 'type');
        $update2 = new Update(['topic2'], 'data', true, 'id', 'type');

        $this->assertFalse($privacy->subscriberCanReceive($subscriber, $update));
        $this->assertTrue($privacy->subscriberCanReceive($subscriber, $update2));
    }

    public function testItAllowPrivateTopicWithWildcardSubscriber(): void
    {
        $privacy = new Privacy();
        $subscriber = new Subscriber(['*'], ['topic1']);
        $update = new Update(['topic1'], 'data', true, 'id', null);

        $this->assertTrue($privacy->subscriberCanReceive($subscriber, $update));
    }
}
