<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class AgeRangeValidatingRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private AgeRangeValidatingRequestBodyParser $parser;

    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->parser = new AgeRangeValidatingRequestBodyParser();
        $this->requestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_rejects_a_typical_age_range_combined_with_a_birthdate_range(): void
    {
        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody((object) [
                'typicalAgeRange' => '6-12',
                'birthdateRange' => (object) [
                    'from' => '2014-01-01',
                    'to' => '2020-12-31',
                ],
            ]);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::cannotCombineTypicalAgeAndBirthdateRange(),
            fn () => $this->parser->parse($request)
        );
    }

    /**
     * @test
     * @dataProvider singleRangeProvider
     */
    public function it_allows_one_of_the_two_ranges(array $body): void
    {
        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody((object) $body);

        $this->assertSame($request, $this->parser->parse($request));
    }

    public function singleRangeProvider(): array
    {
        return [
            'only a typicalAgeRange' => [['typicalAgeRange' => '6-12']],
            'only a birthdateRange' => [
                ['birthdateRange' => (object) ['from' => '2014-01-01', 'to' => '2020-12-31']],
            ],
            'neither' => [[]],
        ];
    }

    /**
     * @test
     */
    public function it_rejects_a_typical_age_range_where_from_is_greater_than_to(): void
    {
        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody((object) ['typicalAgeRange' => '12-6']);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/typicalAgeRange',
                    '"From" age should not be greater than the "to" age.'
                )
            ),
            fn () => $this->parser->parse($request)
        );
    }
}
