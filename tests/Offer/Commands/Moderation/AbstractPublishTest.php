<?php

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AbstractPublishTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_store_a_future_publication_date(): void
    {
        $futurePublicationDate = \DateTimeImmutable::createFromFormat(
            DATE_ATOM,
            Chronos::now()->addWeek()->format(DATE_ATOM)
        );

        $publishCommand = $this->getMockForAbstractClass(
            AbstractPublish::class,
            [(new UUID())->toNative(), $futurePublicationDate]
        );

        $this->assertEquals(
            $futurePublicationDate,
            $publishCommand->getPublicationDate()
        );
    }

    /**
     * @test
     */
    public function it_has_a_default_publication_date_of_now(): void
    {
        $now = Chronos::now();
        Chronos::setTestNow($now);

        $publishCommand = $this->getMockForAbstractClass(
            AbstractPublish::class,
            [(new UUID())->toNative()]
        );

        $publicationDate = $publishCommand->getPublicationDate();

        $this->assertEquals($now, $publicationDate);

        // Clear fixed time
        Chronos::setTestNow();
    }

    /**
     * @test
     */
    public function it_will_default_to_now_if_publication_date_is_in_the_past(): void
    {
        $now = Chronos::now();
        $lastMonth = $now->subMonth();
        Chronos::setTestNow($now);

        /** @var AbstractPublish $abstractPublish */
        $publishCommand = $this->getMockForAbstractClass(
            AbstractPublish::class,
            [(new UUID())->toNative(), $lastMonth]
        );

        $this->assertEquals($now, $publishCommand->getPublicationDate());

        // Clear fixed time
        Chronos::setTestNow();
    }
}
