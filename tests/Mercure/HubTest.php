<?php

namespace Mercure;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use SwagIndustries\MercureRouter\Mercure\Hub;
use SwagIndustries\MercureRouter\Mercure\Privacy;
use SwagIndustries\MercureRouter\Mercure\Store\EventStoreInterface;
use SwagIndustries\MercureRouter\Mercure\Store\InMemoryEventStore;
use SwagIndustries\MercureRouter\Mercure\Subscriber;
use SwagIndustries\MercureRouter\Mercure\Update;

class HubTest extends TestCase
{
    use ProphecyTrait;
    private $privacy;

    private $eventStore;
    private $subject;
    protected function setUp(): void
    {
        parent::setUp();
        $this->privacy = $this->prophesize(Privacy::class);
        $this->eventStore = $this->prophesize(EventStoreInterface::class);
        $this->subject = new Hub($this->eventStore->reveal(), $this->privacy->reveal());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->privacy = null;
        $this->eventStore = null;
        $this->subject = null;
    }

    /**
     * @dataProvider providesPublishExamples
     * @param array<array{subscriber: Subscriber, dispatch: boolean}> $securityMap
     */
    public function testItPublish(Update $update, array $securityMap)
    {
        $this->eventStore->store($update)->shouldBeCalledOnce();
        foreach ($securityMap as $securityItem) {
            $this->subject->addSubscriber($securityItem['subscriber']);
            $this->privacy->subscriberCanReceive(
                $securityItem['subscriber'],
                $update
            )->willReturn($securityItem['dispatch']);
        }

        $this->subject->publish($update);
    }

    public function providesPublishExamples()
    {
        $update = new Update(['faketopic'], 'fakedata', false, 'fakeid', 'faketype');
        $subscriber = $this->prophesize(Subscriber::class);
        $subscriber->dispatch($update)->shouldBeCalled();
        yield '1 subscriber can receive update' => [
            'update' => $update,
            'securityMap' => [
                ['subscriber' => $subscriber->reveal(), 'dispatch' => true],
            ]
        ];

        $update = new Update(['faketopic'], 'fakedata', false, 'fakeid', 'faketype');
        $subscriber = $this->prophesize(Subscriber::class);
        $subscriber->dispatch($update)->shouldBeCalled();
        $subscriber2 = $this->prophesize(Subscriber::class);
        $subscriber2->dispatch($update)->shouldBeCalled();
        yield '2 subscribers can receive update' => [
            'update' => $update,
            'securityMap' => [
                ['subscriber' => $subscriber->reveal(), 'dispatch' => true],
                ['subscriber' => $subscriber2->reveal(), 'dispatch' => true]
            ]
        ];

        $update = new Update(['faketopic'], 'fakedata', false, 'fakeid', 'faketype');
        $subscriber = $this->prophesize(Subscriber::class);
        $subscriber->dispatch($update)->shouldBeCalled();
        $subscriber2 = $this->prophesize(Subscriber::class);
        $subscriber2->dispatch($update)->shouldBeCalled();
        yield '2 subscribers, one can receive update' => [
            'update' => $update,
            'securityMap' => [
                ['subscriber' => $subscriber->reveal(), 'dispatch' => true],
                ['subscriber' => $subscriber2->reveal(), 'dispatch' => false]
            ]
        ];
    }
}
