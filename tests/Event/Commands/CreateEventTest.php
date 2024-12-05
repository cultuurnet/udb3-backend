<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CreateEventTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_return_default_available_from_when_date_in_the_past_was_given(): void
    {
        $id = '5e36d2f2-b5de-4f5e-81b3-a129d996e9b6';
        $language = new Language('nl');
        $title = new Title('some representative title');
        $type = new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType());
        $location = new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015');
        $calendar = new Calendar(CalendarType::permanent());
        $theme = new Category(new CategoryID('0.1.0.1.0.1'), new CategoryLabel('blues'), CategoryDomain::theme());

        $publicationDate = new DateTimeImmutable('2019-02-14');
        $now = new DateTimeImmutable();

        $command = new CreateEvent(
            $id,
            $language,
            $title,
            $type,
            $location,
            $calendar,
            $theme,
            $publicationDate
        );

        $this->assertEquals($now, $command->getPublicationDate($now));
    }

    /**
     * @test
     */
    public function it_will_return_null_if_no_publication_date_was_given(): void
    {
        $id = '5e36d2f2-b5de-4f5e-81b3-a129d996e9b6';
        $language = new Language('nl');
        $title = new Title('some representative title');
        $type = new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType());
        $location = new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015');
        $calendar = new Calendar(CalendarType::permanent());
        $theme = new Category(new CategoryID('0.1.0.1.0.1'), new CategoryLabel('blues'), CategoryDomain::theme());

        $command = new CreateEvent(
            $id,
            $language,
            $title,
            $type,
            $location,
            $calendar,
            $theme
        );

        $this->assertNull($command->getPublicationDate(new DateTimeImmutable()));
    }
}
