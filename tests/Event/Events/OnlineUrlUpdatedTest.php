<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;

final class OnlineUrlUpdatedTest extends TestCase
{
    private OnlineUrlUpdated $onlineUrlUpdated;

    protected function setUp(): void
    {
        $this->onlineUrlUpdated = new OnlineUrlUpdated(
            '8066261d-eaf2-4414-8874-e210d665e896',
            'https://www.publiq.be/livestream'
        );
    }

    /**
     * @test
     */
    public function it_stores_an_event_id(): void
    {
        $this->assertEquals(
            '8066261d-eaf2-4414-8874-e210d665e896',
            $this->onlineUrlUpdated->getEventId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_online_url(): void
    {
        $this->assertEquals(
            'https://www.publiq.be/livestream',
            $this->onlineUrlUpdated->getOnlineUrl()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $this->assertEquals(
            $this->onlineUrlUpdated,
            OnlineUrlUpdated::deserialize(
                [
                    'eventId' => '8066261d-eaf2-4414-8874-e210d665e896',
                    'onlineUrl' => 'https://www.publiq.be/livestream',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            [
                'eventId' => '8066261d-eaf2-4414-8874-e210d665e896',
                'onlineUrl' => 'https://www.publiq.be/livestream',
            ],
            $this->onlineUrlUpdated->serialize()
        );
    }
}
