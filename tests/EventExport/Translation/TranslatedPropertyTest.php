<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Translation;

use CultuurNet\UDB3\Json;
use PHPUnit\Framework\TestCase;
use stdClass;

final class TranslatedPropertyTest extends TestCase
{
    /**
     * @test
     */
    public function it_reads_the_main_language_of_a_document(): void
    {
        $this->assertSame('fr', TranslatedProperty::mainLanguage($this->document(['mainLanguage' => 'fr'])));
    }

    /**
     * @test
     */
    public function it_falls_back_to_dutch_without_a_main_language(): void
    {
        $this->assertSame('nl', TranslatedProperty::mainLanguage($this->document([])));
    }

    /**
     * @test
     */
    public function it_falls_back_to_dutch_for_a_main_language_that_is_not_a_string(): void
    {
        $this->assertSame('nl', TranslatedProperty::mainLanguage($this->document(['mainLanguage' => 12])));
    }

    /**
     * @test
     */
    public function it_reads_a_string_in_its_main_language(): void
    {
        $this->assertSame(
            'Gare Centrale',
            TranslatedProperty::asString(
                $this->document(['nl' => 'Centraal Station', 'fr' => 'Gare Centrale']),
                'fr'
            )
        );
    }

    /**
     * @test
     */
    public function it_reads_a_string_in_any_language_when_the_main_language_is_missing(): void
    {
        $this->assertSame(
            'Centraal Station',
            TranslatedProperty::asString($this->document(['nl' => 'Centraal Station']), 'fr')
        );
    }

    /**
     * @test
     */
    public function it_reads_a_string_that_was_never_translated(): void
    {
        $this->assertSame('Centraal Station', TranslatedProperty::asString('Centraal Station', 'nl'));
    }

    /**
     * @test
     * @dataProvider valuesThatAreNoString
     */
    public function it_reads_nothing_from_a_value_that_is_no_string(mixed $value): void
    {
        $this->assertSame('', TranslatedProperty::asString($value, 'nl'));
    }

    public function valuesThatAreNoString(): array
    {
        return [
            'nothing at all' => ['value' => null],
            'an empty list of translations' => ['value' => new stdClass()],
            'a translation that is no string' => ['value' => (object) ['nl' => 12]],
        ];
    }

    /**
     * @test
     */
    public function it_reads_an_address_field_that_was_never_translated(): void
    {
        $this->assertSame(
            '2000',
            TranslatedProperty::addressField($this->document(['postalCode' => '2000']), 'postalCode', 'nl')
        );
    }

    /**
     * @test
     */
    public function it_reads_an_address_field_in_its_main_language(): void
    {
        $address = $this->document([
            'nl' => ['addressLocality' => 'Antwerpen'],
            'fr' => ['addressLocality' => 'Anvers'],
        ]);

        $this->assertSame('Anvers', TranslatedProperty::addressField($address, 'addressLocality', 'fr'));
    }

    /**
     * @test
     */
    public function it_reads_an_address_field_in_any_language_when_the_main_language_is_missing(): void
    {
        $address = $this->document(['nl' => ['addressLocality' => 'Antwerpen']]);

        $this->assertSame('Antwerpen', TranslatedProperty::addressField($address, 'addressLocality', 'fr'));
    }

    /**
     * @test
     */
    public function it_reads_an_address_field_as_a_string(): void
    {
        $this->assertSame(
            '2000',
            TranslatedProperty::addressField($this->document(['nl' => ['postalCode' => 2000]]), 'postalCode', 'nl')
        );
    }

    /**
     * @test
     * @dataProvider addressesWithoutTheField
     */
    public function it_reads_nothing_from_an_address_without_the_field(mixed $address): void
    {
        $this->assertSame('', TranslatedProperty::addressField($address, 'postalCode', 'nl'));
    }

    public function addressesWithoutTheField(): array
    {
        return [
            'no address at all' => ['address' => null],
            'an empty address' => ['address' => new stdClass()],
            'another field' => ['address' => (object) ['nl' => (object) ['addressLocality' => 'Antwerpen']]],
        ];
    }

    private function document(array $properties): stdClass
    {
        return Json::decode(Json::encode((object) $properties));
    }
}
