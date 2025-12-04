<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Address;

use CultuurNet\UDB3\Address\StreetSuggester\StreetSuggester;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetStreetRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private StreetSuggester&MockObject $streetSuggester;

    private StreetSuggester&MockObject $steetSuggester2;

    private GetStreetRequestHandler $getStreetRequestHandler;

    protected function setUp(): void
    {
        $this->streetSuggester = $this->createMock(StreetSuggester::class);
        $this->steetSuggester2 = $this->createMock(StreetSuggester::class);
        $this->getStreetRequestHandler = new GetStreetRequestHandler($this->streetSuggester, $this->steetSuggester2);
    }

    /**
     * @test
     */
    public function it_can_handle_a_get_street_request(): void
    {
        $request = (new Psr7RequestBuilder())
        ->withUriFromString('streets?country=BE&postalCode=9000&locality=Gent&query=Maria')
        ->build('GET');

        $this->streetSuggester->expects($this->once())
            ->method('suggest')
            ->with('9000', 'Gent', 'Maria', 5)
            ->willReturn([
                'Koningin Maria Hendrikaplein',
                'Marialand',
                'Maria-Theresiastraat',
                'Maria Van Boergondiëstraat',
            ]);

        $response = $this->getStreetRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'Koningin Maria Hendrikaplein',
                    'Marialand',
                    'Maria-Theresiastraat',
                    'Maria Van Boergondiëstraat',
                ],
            ),
            $response
        );
    }

    /**
     * @test
     */
    public function it_supports_limits(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('streets?country=BE&postalCode=9000&locality=Gent&query=Maria&limit=2')
            ->build('GET');

        $this->streetSuggester->expects($this->once())
            ->method('suggest')
            ->with('9000', 'Gent', 'Maria', 2)
            ->willReturn([
                'Koningin Maria Hendrikaplein',
                'Marialand',
            ]);

        $response = $this->getStreetRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'Koningin Maria Hendrikaplein',
                    'Marialand',
                ],
            ),
            $response
        );
    }

    /**
     * @test
     */
    public function it_will_throw_on_unsupported_countries(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('streets?country=DK&postalCode=1012AB&locality=Amsterdam&query=Maria')
            ->build('GET');

        $this->streetSuggester->expects($this->never())
            ->method('suggest');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterInvalidValue('country', 'DK', ['BE', 'NLD']),
            fn () => $this->getStreetRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider missingQueryParams
     */
    public function it_will_throw_on_missing_query_params(string $uri): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString($uri)
            ->build('GET');

        $this->streetSuggester->expects($this->never())
            ->method('suggest');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterMissing('country or postalCode or locality or query'),
            fn () => $this->getStreetRequestHandler->handle($request)
        );
    }

    public function missingQueryParams(): array
    {
        return  [
            'missing everything' => ['streets?'],
            'missing country' => ['streets?postalCode=9000&locality=Gent&query=Maria'],
            'missing postalCode' => ['streets?country=BE&locality=Gent&query=Maria'],
            'missing locality' => ['streets?country=BE&postalCode=9000&query=Maria'],
            'missing query' => ['streets?country=BE&postalCode=9000&locality=Gent'],
        ];
    }
}
