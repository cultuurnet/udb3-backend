<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\Parser;

use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CachingAddressParserTest extends TestCase
{
    /** @var AddressParser&MockObject  */
    private AddressParser $addressParser;
    private CachingAddressParser $cachingAddressParser;

    protected function setUp(): void
    {
        $this->addressParser = $this->createMock(AddressParser::class);
        $this->cachingAddressParser = new CachingAddressParser($this->addressParser, new ArrayCache());
    }

    /**
     * @test
     */
    public function it_parses_an_address_once_and_then_returns_cached_results(): void
    {
        $formatted = 'Martelarenplein 1, 3000 Leuven, BE';
        $expected = new ParsedAddress('Martelarenplein', '1', '3000', 'Leuven');

        $this->addressParser->expects($this->once())
            ->method('parse')
            ->with($formatted)
            ->willReturn($expected);

        $first = $this->cachingAddressParser->parse($formatted);
        $second = $this->cachingAddressParser->parse($formatted);
        $third = $this->cachingAddressParser->parse($formatted);

        $this->assertEquals($expected, $first);
        $this->assertEquals($expected, $second);
        $this->assertEquals($expected, $third);
    }
}
