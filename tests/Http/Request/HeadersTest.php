<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class HeadersTest extends TestCase
{
    use AssertApiProblemTrait;

    private const POSSIBLE_CONTENT_TYPES = [
        'application/ld+json',
        'application/json',
        'text/html',
        'text/xml',
    ];

    /**
     * @test
     */
    public function it_should_return_the_first_possible_content_type_if_no_accept_header_is_given(): void
    {
        $request = (new Psr7RequestBuilder())->build('GET');
        $headers = new Headers($request);
        $expected = 'application/ld+json';
        $actual = $headers->determineResponseContentType(self::POSSIBLE_CONTENT_TYPES);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_the_first_possible_content_type_if_the_accept_header_is_empty(): void
    {
        $request = $this->createRequestWithAcceptHeader('');
        $headers = new Headers($request);
        $expected = 'application/ld+json';
        $actual = $headers->determineResponseContentType(self::POSSIBLE_CONTENT_TYPES);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_the_first_possible_content_type_if_the_accept_header_is_a_wildcard(): void
    {
        $request = $this->createRequestWithAcceptHeader('*');
        $headers = new Headers($request);
        $expected = 'application/ld+json';
        $actual = $headers->determineResponseContentType(self::POSSIBLE_CONTENT_TYPES);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_the_first_possible_content_type_if_the_accept_header_is_a_double_wildcard(): void
    {
        $request = $this->createRequestWithAcceptHeader('*/*');
        $headers = new Headers($request);
        $expected = 'application/ld+json';
        $actual = $headers->determineResponseContentType(self::POSSIBLE_CONTENT_TYPES);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_the_first_text_type_if_the_subtype_is_a_wildcard(): void
    {
        $request = $this->createRequestWithAcceptHeader('text/*');
        $headers = new Headers($request);
        $expected = 'text/html';
        $actual = $headers->determineResponseContentType(self::POSSIBLE_CONTENT_TYPES);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_specific_media_type_that_is_accepted_and_possible(): void
    {
        $request = $this->createRequestWithAcceptHeader('application/json');
        $headers = new Headers($request);
        $expected = 'application/json';
        $actual = $headers->determineResponseContentType(self::POSSIBLE_CONTENT_TYPES);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_the_type_highest_in_the_list_of_possible_types_if_multiple_are_possible(): void
    {
        $request = $this->createRequestWithAcceptHeader('text/html, application/json, text/xml');
        $headers = new Headers($request);
        $expected = 'application/json';
        $actual = $headers->determineResponseContentType(self::POSSIBLE_CONTENT_TYPES);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_ignore_impossible_media_types(): void
    {
        $request = $this->createRequestWithAcceptHeader('image/gif, text/xml, image/png');
        $headers = new Headers($request);
        $expected = 'text/xml';
        $actual = $headers->determineResponseContentType(self::POSSIBLE_CONTENT_TYPES);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_throw_if_only_invalid_media_types_are_accepted(): void
    {
        $request = $this->createRequestWithAcceptHeader('image/gif, image/png');
        $headers = new Headers($request);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::notAcceptable('Acceptable media types are: application/ld+json, application/json, text/html, text/xml'),
            fn () => $headers->determineResponseContentType(self::POSSIBLE_CONTENT_TYPES)
        );
    }

    private function createRequestWithAcceptHeader(string $accept): ServerRequestInterface
    {
        return (new Psr7RequestBuilder())
            ->withHeader('accept', $accept)
            ->build('GET');
    }
}
