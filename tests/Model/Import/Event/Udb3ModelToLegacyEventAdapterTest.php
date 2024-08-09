<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Event;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\Event\ImmutableEvent;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class Udb3ModelToLegacyEventAdapterTest extends TestCase
{
    private Udb3ModelToLegacyEventAdapter $adapter;

    private Udb3ModelToLegacyEventAdapter $completeAdapter;

    public function setUp(): void
    {
        $event = new ImmutableEvent(
            new UUID('91060c19-a860-4a47-8591-8a779bfa520a'),
            new Language('nl'),
            (new TranslatedTitle(new Language('nl'), new Title('Voorbeeld titel')))
                ->withTranslation(new Language('fr'), new Title('Titre example'))
                ->withTranslation(new Language('en'), new Title('Example title')),
            new PermanentCalendar(new OpeningHours()),
            PlaceReference::createWithPlaceId(
                new UUID('6ba87a6b-efea-4467-9e87-458d145384d9'),
            ),
            new Categories(
                new Category(
                    new CategoryID('0.6.0.0.0'),
                    new CategoryLabel('Beurs'),
                    new CategoryDomain('eventtype')
                ),
                new Category(
                    new CategoryID('0.52.0.0.0'),
                    new CategoryLabel('Circus'),
                    new CategoryDomain('theme')
                )
            )
        );

        $completeEvent = $event
            ->withAudienceType(
                AudienceType::members()
            )
            ->withAvailableFrom(
                DateTimeFactory::fromAtom('2018-01-01T10:00:00+01:00')
            );

        $this->adapter = new Udb3ModelToLegacyEventAdapter($event);
        $this->completeAdapter = new Udb3ModelToLegacyEventAdapter($completeEvent);
    }

    /**
     * @test
     */
    public function it_should_return_the_embedded_location(): void
    {
        $expected = new LocationId('6ba87a6b-efea-4467-9e87-458d145384d9');
        $actual = $this->adapter->getLocation();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_audience_type_everyone_by_default(): void
    {
        $expected = AudienceType::everyone();
        $actual = $this->adapter->getAudienceType();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_the_audience_type_that_was_set(): void
    {
        $expected = AudienceType::members();
        $actual = $this->completeAdapter->getAudienceType();
        $this->assertEquals($expected, $actual);
    }
}
