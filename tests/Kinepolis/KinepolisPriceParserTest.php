<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use PHPUnit\Framework\TestCase;

final class KinepolisPriceParserTest extends TestCase
{
    private KinepolisPriceParser $priceParser;

    public function setUp(): void
    {
        $this->priceParser = new KinepolisPriceParser();
    }

    /**
     * @test
     */
    public function it_parses_prices_from_a_theater(): void
    {
        $this->assertEquals(
            new ParsedPrice(
                1380,
                1280,
                1035,
                100,
                150
            ),
            $this->priceParser->parseTheaterPrices(
                [
                    ['€ 13,80', 'Normaal tarief'],
                    ['€ 12,80', 'Kortingstarief'],
                    ['€ 10,35', 'Kinepolis Student Card'],
                    ['€ 1,50', 'Supplement 3D'],
                    ['€ 1,50', 'Eénmalige aankoop persoonlijke 3D bril'],
                    ['€ 1,50', 'Eénmalige aankoop persoonlijke 3D clip-on bril'],
                    ['', ''],
                    ['€ 1,00', 'Supplement Film Lange Speelduur (>/=2u15)'],
                ]
            )
        );
    }
}
