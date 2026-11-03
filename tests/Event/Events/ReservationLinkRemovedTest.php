<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class ReservationLinkRemovedTest extends TestCase
{
    private ReservationLinkRemoved $reservationLinkRemoved;

    protected function setUp(): void
    {
        $this->reservationLinkRemoved = new ReservationLinkRemoved(
            '8066261d-eaf2-4414-8874-e210d665e896',
            new Url('https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345')
        );
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            [
                'item_id' => '8066261d-eaf2-4414-8874-e210d665e896',
                'reservation_link' => 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345',
            ],
            $this->reservationLinkRemoved->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $this->assertEquals(
            $this->reservationLinkRemoved,
            ReservationLinkRemoved::deserialize(
                [
                    'item_id' => '8066261d-eaf2-4414-8874-e210d665e896',
                    'reservation_link' => 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345',
                ]
            )
        );
    }
}
