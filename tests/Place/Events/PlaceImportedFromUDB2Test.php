<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;

final class PlaceImportedFromUDB2Test extends TestCase
{
    /**
     * @test
     */
    public function it_implements_main_language_defined(): void
    {
        $event = new PlaceImportedFromUDB2(
            '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
            file_get_contents(__DIR__ . '/../actor.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertInstanceOf(MainLanguageDefined::class, $event);
        $this->assertEquals(new Language('nl'), $event->getMainLanguage());
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_modern_granular_events(): void
    {
        $placeImportedFromUDB2 = new PlaceImportedFromUDB2(
            '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
            file_get_contents(__DIR__ . '/../actor2.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated('0452b4ae-7c18-4b33-a6c6-eba2288c9ac3', new Title('CC Palethe')),
            ],
            $placeImportedFromUDB2->toGranularEvents()
        );
    }
}
