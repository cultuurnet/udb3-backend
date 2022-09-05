<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\RemoveLabel;
use PHPUnit\Framework\TestCase;

class RemoveLabelRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const VALID_LABEL_NAME = 'Some old Label';
    private const INVALID_LABEL_NAME = 'Some;old;invalid;Label';

    private TraceableCommandBus $commandBus;

    private RemoveLabelRequestHandler $removeLabelRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->removeLabelRequestHandler = new RemoveLabelRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_removing_both_valid_and_invalid_labels_of_an_offer(
        string $offerType,
        string $labelName,
        RemoveLabel $removeLabel
    ): void {
        $removeLabelRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('labelName', $labelName)
            ->build('DELETE');

        $response = $this->removeLabelRequestHandler->handle($removeLabelRequest);

        $this->assertEquals(
            [
                $removeLabel,
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    public function offerTypeDataProvider(): array
    {
        return [
            [
                'offerType' => 'events',
                'labelName' => self::VALID_LABEL_NAME,
                'removeLabel' => new RemoveLabel(
                    self::OFFER_ID,
                    self::VALID_LABEL_NAME
                ),
            ],
            [
                'offerType' => 'places',
                'labelName' => self::VALID_LABEL_NAME,
                'removeLabel' => new RemoveLabel(
                    self::OFFER_ID,
                    self::VALID_LABEL_NAME
                ),
            ],
            [
                'offerType' => 'events',
                'labelName' => self::INVALID_LABEL_NAME,
                'removeLabel' => new RemoveLabel(
                    self::OFFER_ID,
                    self::INVALID_LABEL_NAME
                ),
            ],
            [
                'offerType' => 'places',
                'labelName' => self::INVALID_LABEL_NAME,
                'removeLabel' => new RemoveLabel(
                    self::OFFER_ID,
                    self::INVALID_LABEL_NAME
                ),
            ],
        ];
    }
}
