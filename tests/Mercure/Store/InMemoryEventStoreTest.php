<?php

namespace SwagIndustries\MercureRouter\Test\Mercure\Store;

use Amp\PHPUnit\AsyncTestCase;
use SwagIndustries\MercureRouter\Mercure\Store\InMemoryEventStore;
use SwagIndustries\MercureRouter\Mercure\Update;

class InMemoryEventStoreTest extends AsyncTestCase
{
    public function testItStoreEventsAndIsAbleToRetrieveThem()
    {
        $event1 = new Update(['some-topic'], 'message data', true, '1', null);
        $event2 = new Update(['some-topic'], 'message data 2', true, '2', null);
        $store = new InMemoryEventStore();
        $store->store($event1);
        $store->store($event2);
        $reconciliation = $store->reconcile('2');

        $this->assertEquals([$event2], $reconciliation);
    }
}
