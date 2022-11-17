<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\Commands\UpdateContactPoint as EventUpdateContactPoint;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateContactPoint;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint as PlaceUpdateContactPoint;
use Iterator;
use PHPUnit\Framework\TestCase;

final class UpdateContactPointRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';

    private TraceableCommandBus $commandBus;

    private UpdateContactPointRequestHandler $updateContactPointRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateContactPointRequestHandler = new UpdateContactPointRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_updating_the_contact_point_of_an_offer(
        string $offerType,
        array $request,
        AbstractUpdateContactPoint $expectedCommand
    ): void {
        $updateContactPointRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray($request)
            ->build('PUT');

        $response = $this->updateContactPointRequestHandler->handle($updateContactPointRequest);

        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    /**
     * @test
     * @dataProvider provideInvalidRequestBodies
     */
    public function it_throws_updating_when_contact_point_is_incomplete(
        string $offerType,
        array $request,
        ApiProblem $expectedProblem
    ): void {
        $updateContactPointRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray($request)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            $expectedProblem,
            fn () => $this->updateContactPointRequestHandler->handle($updateContactPointRequest)
        );
    }

    public function provideInvalidRequestBodies(): Iterator
    {
        $offers = ['events', 'places'];

        foreach ($offers as $offerType) {
            yield 'all properties missing ' . $offerType => [
                'offerType' => $offerType,
                'request' => [
                    'mail' => ['info@publiq.be'],
                ],
                'expectedProblem' => ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The required properties (phone, email, url) are missing')
                ),
            ];

            yield 'all properties are strings iso arrays ' . $offerType => [
                'offerType' => $offerType,
                'request' => [
                    'phone' => '0475/123123',
                    'email' => 'info@publiq.be',
                    'url' => 'https://www.publiq.be/',
                ],
                'expectedProblem' => ApiProblem::bodyInvalidData(
                    new SchemaError('/phone', 'The data (string) must match the type: array'),
                    new SchemaError('/email', 'The data (string) must match the type: array'),
                    new SchemaError('/url', 'The data (string) must match the type: array'),
                ),
            ];
        }
    }

    public function offerTypeDataProvider(): Iterator
    {
        $offers = [
            'events'=> EventUpdateContactPoint::class,
            'places' => PlaceUpdateContactPoint::class,
        ];

        foreach ($offers as $offerType => $offerCommand) {
            yield 'legacy format ' . $offerType => [
                'offerType' => $offerType,
                'request' => [
                    'contactPoint' => [
                        'url' => ['https://www.publiq.be/'],
                        'email' => [],
                        'phone' => ['0475/123123', '02/123123'],
                    ],
                ],
                'expectedCommand' => new $offerCommand(
                    self::OFFER_ID,
                    new ContactPoint(
                        ['0475/123123', '02/123123'],
                        [],
                        ['https://www.publiq.be/']
                    )
                ),
            ];

            yield 'all properties are empty arrays ' . $offerType => [
                'offerType' => $offerType,
                'request' => [
                    'phone' => [],
                    'email' => [],
                    'url' => [],
                ],
                'expectedCommand' => new $offerCommand(
                    self::OFFER_ID,
                    new ContactPoint([], [], []),
                ),
            ];

            yield 'all properties have an empty string ' . $offerType => [
                'offerType' => $offerType,
                'request' => [
                    'phone' => [''],
                    'email' => [''],
                    'url' => [''],
                ],
                'expectedCommand' => new $offerCommand(
                    self::OFFER_ID,
                    new ContactPoint([''], [''], ['']),
                ),
            ];

            yield 'all properties filled in with one value ' . $offerType => [
                'offerType' => $offerType,
                'request' => [
                    'url' => ['https://www.publiq.be/'],
                    'email' => ['info@publiq.be'],
                    'phone' => ['0475/123123'],
                ],
                'expectedCommand' => new $offerCommand(
                    self::OFFER_ID,
                    new ContactPoint(
                        ['0475/123123'],
                        ['info@publiq.be'],
                        ['https://www.publiq.be/']
                    )
                ),
            ];

            yield 'all properties filled in with multiple values ' . $offerType => [
                'offerType' => $offerType,
                'request' => [
                    'url' => ['https://www.publiq.be/', 'https://madewithlove.com'],
                    'email' => ['info@publiq.be', 'info@madewithlove.com'],
                    'phone' => ['0475/123123', '0473/123456'],
                ],
                'expectedCommand' => new $offerCommand(
                    self::OFFER_ID,
                    new ContactPoint(
                        ['0475/123123', '0473/123456'],
                        ['info@publiq.be', 'info@madewithlove.com'],
                        ['https://www.publiq.be/', 'https://madewithlove.com'],
                    )
                ),
            ];
        }
    }
}
