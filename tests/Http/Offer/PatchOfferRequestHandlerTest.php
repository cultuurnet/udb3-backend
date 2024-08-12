<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\Commands\Moderation\Approve;
use CultuurNet\UDB3\Event\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Event\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish;
use CultuurNet\UDB3\Event\Commands\Moderation\Reject;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Place\Commands\Moderation\Approve as ApprovePlace;
use CultuurNet\UDB3\Place\Commands\Moderation\FlagAsDuplicate as FlagAsDuplicatePlace;
use CultuurNet\UDB3\Place\Commands\Moderation\FlagAsInappropriate as FlagAsInappropriatePlace;
use CultuurNet\UDB3\Place\Commands\Moderation\Publish as PublishPlace;
use CultuurNet\UDB3\Place\Commands\Moderation\Reject as RejectPlace;
use PHPUnit\Framework\TestCase;

final class PatchOfferRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    private string $offerId = 'e6238239-4ec1-4778-a0ca-bf7fb0256eed';

    private TraceableCommandBus $commandBus;

    private PatchOfferRequestHandler $patchOfferRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->patchOfferRequestHandler = new PatchOfferRequestHandler($this->commandBus);
    }

    /**
     * @test
     * @dataProvider commandRequestDataProvider
     */
    public function it_dispatches_workflow_commands(
        string $offerType,
        string $header,
        array $body,
        AbstractCommand $expectedCommand
    ): void {
        $requestBuilder = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', $this->offerId)
            ->withHeader('Content-Type', $header);

        if (!empty($body)) {
            $requestBuilder = $requestBuilder->withJsonBodyFromArray($body);
        }

        $this->commandBus->record();

        $response = $this->patchOfferRequestHandler->handle($requestBuilder->build('PATCH'));

        $this->assertEquals(
            [$expectedCommand],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(new NoContentResponse(), $response);
    }

    public function commandRequestDataProvider(): array
    {
        return [
            'Approve event' => [
                'offerType' => 'events',
                'header' => 'application/ld+json;domain-model=Approve',
                'body' => [],
                'expectedCommand' => new Approve($this->offerId),
            ],
            'Reject event' => [
                'offerType' => 'events',
                'request' => 'application/ld+json;domain-model=Reject',
                'body' => ['reason' => 'Description missing :('],
                'expectedCommand' => new Reject($this->offerId, 'Description missing :('),
            ],
            'Flag event as duplicate' => [
                'offerType' => 'events',
                'header' => 'application/ld+json;domain-model=FlagAsDuplicate',
                'body' => [],
                'expectedCommand' => new FlagAsDuplicate($this->offerId),
            ],
            'Flag event as inappropriate' => [
                'offerType' => 'events',
                'header' => 'application/ld+json;domain-model=FlagAsInappropriate',
                'body' => [],
                'expectedCommand' => new FlagAsInappropriate($this->offerId),
            ],
            'Publish event with publication date' => [
                'offerType' => 'events',
                'request' => 'application/ld+json;domain-model=Publish',
                'body' => ['publicationDate' => '2030-02-01T12:00:00+00:00'],
                'expectedCommand' => new Publish(
                    $this->offerId,
                    DateTimeFactory::fromAtom('2030-02-01T12:00:00+00:00')
                ),
            ],
            'Approve place' => [
                'offerType' => 'places',
                'request' => 'application/ld+json;domain-model=Approve',
                'body' => [],
                'expectedCommand' => new ApprovePlace($this->offerId),
            ],
            'Reject place' => [
                'offerType' => 'places',
                'request' => 'application/ld+json;domain-model=Reject',
                'body' => ['reason' => 'Description missing :('],
                'expectedCommand' => new RejectPlace($this->offerId, 'Description missing :('),
            ],
            'Flag place as duplicate' => [
                'offerType' => 'places',
                'request' => 'application/ld+json;domain-model=FlagAsDuplicate',
                'body' => [],
                'expectedCommand' => new FlagAsDuplicatePlace($this->offerId),
            ],
            'Flag place as inappropriate' => [
                'offerType' => 'places',
                'request' => 'application/ld+json;domain-model=FlagAsInappropriate',
                'body' => [],
                'expectedCommand' => new FlagAsInappropriatePlace($this->offerId),
            ],
            'Publish place with publication date' => [
                'offerType' => 'places',
                'request' => 'application/ld+json;domain-model=Publish',
                'body' => ['publicationDate' => '2030-02-01T12:00:00+00:00'],
                'expectedCommand' => new PublishPlace(
                    $this->offerId,
                    DateTimeFactory::fromAtom('2030-02-01T12:00:00+00:00')
                ),
            ],
        ];
    }
}
