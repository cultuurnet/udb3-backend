<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Organizer\Commands\UpdateContributors;
use PHPUnit\Framework\TestCase;

final class UpdateContributorsRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private Uuid $organizerId;

    private UpdateContributorsRequestHandler $updateContributorsRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    private TraceableCommandBus $commandBus;

    public function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->organizerId = new Uuid('4c47cbf8-8406-4af6-b6e7-fddd78e0efd8');
        $this->updateContributorsRequestHandler = new UpdateContributorsRequestHandler(
            $this->commandBus
        );

        $this->commandBus->record();
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_handles_updating_contributors(): void
    {
        $validEmails = [
            'jan@gent.be',
            'piet@gent.be',
            'an@gent.be',
        ];
        $updateContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $this->organizerId->toString())
            ->withJsonBodyFromArray($validEmails)
            ->build('PUT');

        $response = $this->updateContributorsRequestHandler->handle($updateContributorsRequest);

        $this->assertEquals(
            [
                new UpdateContributors(
                    $this->organizerId->toString(),
                    EmailAddresses::fromArray(array_map(fn ($email) => new EmailAddress($email), $validEmails))
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
     */
    public function it_handles_invalid_emails(): void
    {
        $invalidContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', $this->organizerId->toString())
            ->withJsonBodyFromArray(
                [
                    'piet@gent.be',
                    'an@gent.be',
                    '09/1231212',
                ]
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/2', 'The data must match the \'email\' format')
            ),
            fn () => $this->updateContributorsRequestHandler->handle($invalidContributorsRequest)
        );
    }
}
