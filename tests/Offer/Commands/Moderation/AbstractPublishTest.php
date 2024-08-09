<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

        /** @var AbstractPublish&MockObject $publishCommand */
        $publishCommand = $this->getMockForAbstractClass(
            AbstractPublish::class,
            ['513b3060-c94c-4aef-bfaa-9ad4fc54d979', $futurePublicationDate]
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

        /** @var AbstractPublish&MockObject $publishCommand */
        $publishCommand = $this->getMockForAbstractClass(
            AbstractPublish::class,
            ['0399351f-89b6-4b16-981e-d4e71b8817e5']
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

        /** @var AbstractPublish&MockObject $publishCommand */
        $publishCommand = $this->getMockForAbstractClass(
            AbstractPublish::class,
            ['b9cf614a-96b1-467e-831f-9f91224994bf', $lastMonth]
        );

        $this->assertEquals($now, $publishCommand->getPublicationDate());

        // Clear fixed time
        Chronos::setTestNow();
    }
}
