<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use PHPUnit\Framework\TestCase;

class PriceDescriptionParserTest extends TestCase
{
    private PriceDescriptionParser $parser;

    public function setUp(): void
    {
        $this->parser = new PriceDescriptionParser(
            new NumberFormatRepository(),
            new CurrencyRepository()
        );
    }

    /**
     * @test
     */
    public function it_parses_valid_description_into_price_key_value_pairs(): void
    {
        $description = 'Basistarief: 12,50 €; Met kinderen: 20,00 €; Senioren: 30,00 €';

        $expectedPrices = [
            'Basistarief' => 12.5,
            'Met kinderen' => 20,
            'Senioren' => 30,
        ];

        $prices = $this->parser->parse($description);

        $this->assertEquals($expectedPrices, $prices);
    }

    /**
     * @test
     */
    public function it_ignores_invalid_descriptions(): void
    {
        $description = 'Met kinderen € 20, Gratis voor grootouders';

        $this->assertSame([], $this->parser->parse($description));
    }

    /**
     * @test
     */
    public function it_ignores_invalid_prices(): void
    {
        $description = 'Met kinderen: € 0,20,0';

        $this->assertSame([], $this->parser->parse($description));
    }

    /**
     * @test
     */
    public function it_ignores_all_prices_when_at_least_one_is_invalid(): void
    {
        // Only the last price is invalid.
        $description = 'Basistarief: 12,50 €; Met kinderen: 20,00 €; Senioren 30,00 €';

        $this->assertSame([], $this->parser->parse($description));
    }
}
