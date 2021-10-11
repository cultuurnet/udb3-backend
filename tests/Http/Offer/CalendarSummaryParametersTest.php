<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

class CalendarSummaryParametersTest extends TestCase
{
    use AssertApiProblemTrait;

    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->requestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_throws_if_the_langCode_parameter_contains_an_invalid_value(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?langCode=foo')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterInvalidValue(
                'langCode',
                'foo',
                ['nl_BE', 'fr_BE', 'de_BE', 'en_BE']
            ),
            fn () => new CalendarSummaryParameters($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_if_the_language_parameter_contains_an_invalid_value(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?language=foo')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterInvalidValue(
                'language',
                'foo',
                ['nl', 'fr', 'de', 'en']
            ),
            fn () => new CalendarSummaryParameters($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_if_the_style_parameter_contains_an_invalid_value(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?style=foo')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterInvalidValue(
                'style',
                'foo',
                ['html', 'text']
            ),
            fn () => new CalendarSummaryParameters($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_if_the_format_parameter_contains_an_invalid_value(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?format=foo')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterInvalidValue(
                'format',
                'foo',
                ['xs', 'sm', 'md', 'lg']
            ),
            fn () => new CalendarSummaryParameters($request)
        );
    }

    /**
     * @test
     */
    public function it_returns_default_values_if_no_parameters_are_set(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary')
            ->build('GET');

        $parameters = new CalendarSummaryParameters($request);

        $this->assertEquals('text/plain', $parameters->getContentType());
        $this->assertEquals('nl', $parameters->getLanguageCode());
        $this->assertEquals('lg', $parameters->getFormat());
        $this->assertEquals(false, $parameters->shouldHidePastDates());
        $this->assertEquals('Europe/Brussels', $parameters->getTimezone());
    }

    /**
     * @test
     * @dataProvider acceptHeaderDataProvider
     */
    public function it_returns_content_type_from_accept_header(string $acceptHeader, string $expectedContentType): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary')
            ->withHeader('accept', $acceptHeader)
            ->build('GET');

        $parameters = new CalendarSummaryParameters($request);

        $this->assertEquals($expectedContentType, $parameters->getContentType());
    }

    public function acceptHeaderDataProvider(): array
    {
        return [
            [
                'given' => 'text/plain',
                'expected' => 'text/plain',
            ],
            [
                'given' => 'text/html',
                'expected' => 'text/html',
            ],
            [
                'given' => 'text/*',
                'expected' => 'text/plain',
            ],
            [
                'given' => '*/*',
                'expected' => 'text/plain',
            ],
            [
                'given' => 'text/html; q=0.2, text/plain',
                'expected' => 'text/html',
            ],
            [
                'given' => 'text/plain; q=0.3, text/html',
                'expected' => 'text/plain',
            ],
            [
                'given' => 'foobar',
                'expected' => 'text/plain',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_returns_content_type_from_style_parameter(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?style=html')
            ->build('GET');

        $parameters = new CalendarSummaryParameters($request);

        $this->assertEquals('text/html', $parameters->getContentType());
    }

    /**
     * @test
     */
    public function it_returns_content_type_from_style_parameter_if_accept_header_is_also_provided(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?style=html')
            ->withHeader('accept', 'text/plain')
            ->build('GET');

        $parameters = new CalendarSummaryParameters($request);

        $this->assertEquals('text/html', $parameters->getContentType());
    }

    /**
     * @test
     */
    public function it_returns_overridden_langCode_parameter_value(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?langCode=fr_BE')
            ->build('GET');

        $parameters = new CalendarSummaryParameters($request);

        $this->assertEquals('fr_BE', $parameters->getLanguageCode());
    }

    /**
     * @test
     */
    public function it_returns_overridden_language_parameter_value(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?language=fr')
            ->build('GET');

        $parameters = new CalendarSummaryParameters($request);

        $this->assertEquals('fr', $parameters->getLanguageCode());
    }

    /**
     * @test
     */
    public function it_returns_overridden_format_parameter_value(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?format=xs')
            ->build('GET');

        $parameters = new CalendarSummaryParameters($request);

        $this->assertEquals('xs', $parameters->getFormat());
    }

    /**
     * @test
     */
    public function it_returns_overridden_hidePast_parameter_value(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?hidePast=false')
            ->build('GET');

        $parameters = new CalendarSummaryParameters($request);

        $this->assertEquals(false, $parameters->shouldHidePastDates());
    }

    /**
     * @test
     */
    public function it_returns_overridden_timezone_parameter_value(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?timezone=Europe/Amsterdam')
            ->build('GET');

        $parameters = new CalendarSummaryParameters($request);

        $this->assertEquals('Europe/Amsterdam', $parameters->getTimezone());
    }

    /**
     * @test
     */
    public function it_returns_overridden_timeZone_parameter_value_in_old_casing(): void
    {
        $request = $this->requestBuilder
            ->withUriFromString('/events/663048bb-33d1-4a92-bfa8-407e43ebd621/calendar-summary?timeZone=Europe/Amsterdam')
            ->build('GET');

        $parameters = new CalendarSummaryParameters($request);

        $this->assertEquals('Europe/Amsterdam', $parameters->getTimezone());
    }
}
