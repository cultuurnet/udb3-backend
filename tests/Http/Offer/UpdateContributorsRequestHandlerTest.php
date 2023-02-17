<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Offer\Commands\UpdateContributors;
use CultuurNet\UDB3\Offer\OfferType;
use PHPUnit\Framework\TestCase;

final class UpdateContributorsRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private UpdateContributorsRequestHandler $updateContributorsRequestHandler;

    private TraceableCommandBus $commandBus;

    private Psr7RequestBuilder $psr7RequestBuilder;


    public function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->updateContributorsRequestHandler = new UpdateContributorsRequestHandler(
            $this->commandBus
        );

        $this->commandBus->record();

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     * @dataProvider offerDataProvider
     */
    public function it_handles_updating_contributors(
        OfferType $offerType,
        string $offerRouteParameter,
        string $offerId
    ): void {
        $validEmails = [
            'jan@gent.be',
            'piet@gent.be',
            'an@gent.be',
        ];

        $updateContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerRouteParameter)
            ->withRouteParameter('offerId', $offerId)
            ->withJsonBodyFromArray($validEmails)
            ->build('PUT');

        $response = $this->updateContributorsRequestHandler->handle($updateContributorsRequest);

        $this->assertEquals(
            [
                new UpdateContributors(
                    $offerId,
                    EmailAddresses::fromArray(array_map(fn ($email) => new EmailAddress($email), $validEmails)),
                    $offerType
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    /**
     * @test
     * @dataProvider offerDataProvider
     */
    public function it_handles_invalid_emails(
        OfferType $offerType,
        string $offerRouteParameter,
        string $offerId
    ): void {
        $invalidContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerRouteParameter)
            ->withRouteParameter('offerId', $offerId)
            ->withJsonBodyFromArray(
                [
                    '09/1231212',
                    'piet@gent.be',
                    'an@gent.be',
                ]
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/0', 'The data must match the \'email\' format')
            ),
            fn () => $this->updateContributorsRequestHandler->handle($invalidContributorsRequest)
        );
    }

    public function offerDataProvider(): array
    {
        return [
            'event' => [
                OfferType::event(),
                'events',
                '4c47cbf8-8406-4af6-b6e7-fddd78e0efd8',
            ],
            'place' => [
                OfferType::place(),
                'places',
                '4ecb33d8-8068-45c9-a58e-e5fb767cb08a',
            ],
        ];
    }
}
