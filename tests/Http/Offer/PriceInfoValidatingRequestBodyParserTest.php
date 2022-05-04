<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class PriceInfoValidatingRequestBodyParserTest extends TestCase
{
    /**
     * @test
     */
    public function it_handles_priceInfo(): void
    {
        $priceInfo = [
            (object) [
                'category' => 'base',
                'name' => (object) [
                    'nl' => 'Basistarief',
                ],
                'price' => 10,
                'priceCurrency' => 'EUR',
            ],
            (object) [
                'category' => 'tariff',
                'name' => 'Senioren',
                'price' => '100',
                'priceCurrency' => 'USD',
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->build('PUT')
            ->withParsedBody($priceInfo);

        $actual = (new PriceInfoValidatingRequestBodyParser())->parse($request)->getParsedBody();

        $this->assertEquals($priceInfo, $actual);
    }

    /**
     * @test
     */
    public function it_throws_on_same_names(): void
    {
        $priceInfo = [
            (object) [
                'category' => 'base',
                'name' => (object) [
                    'nl' => 'Basistarief',
                ],
                'price' => 10,
                'priceCurrency' => 'EUR',
            ],
            (object) [
                'category' => 'tariff',
                'name' => 'Senioren',
                'price' => '100',
                'priceCurrency' => 'USD',
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->build('PUT')
            ->withParsedBody($priceInfo);

        $actual = (new PriceInfoValidatingRequestBodyParser())->parse($request)->getParsedBody();

        $this->assertEquals($priceInfo, $actual);
    }
}
