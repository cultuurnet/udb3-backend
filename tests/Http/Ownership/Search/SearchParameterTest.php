<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SearchParameterTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_an_exception_for_unsupported_url_parameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported url parameter: unsupported');

        new SearchParameter('unsupported', 'value');
    }

    /**
     * @test
     */
    public function it_stores_the_url_parameter_and_value(): void
    {
        $searchParameter = new SearchParameter('itemId', 'value');

        $this->assertEquals('itemId', $searchParameter->getUrlParameter());
        $this->assertEquals('value', $searchParameter->getValue());
    }
}
