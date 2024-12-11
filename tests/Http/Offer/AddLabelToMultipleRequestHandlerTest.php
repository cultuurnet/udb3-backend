<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\AddLabelToMultiple;
use CultuurNet\UDB3\Offer\Commands\AddLabelToMultipleJSONDeserializer;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\IriOfferIdentifierJSONDeserializer;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use CultuurNet\UDB3\Offer\OfferType;
use PHPUnit\Framework\TestCase;

final class AddLabelToMultipleRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const EVENT_ID = 'b5c2c03c-780e-4db9-be3c-70a405d9e071';
    private const PLACE_ID = 'aca838a0-3ae9-439d-a037-3ed78a657c3c';

    private TraceableCommandBus $commandBus;

    private AddLabelToMultipleRequestHandler $addLabelToMultipleRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->addLabelToMultipleRequestHandler = new AddLabelToMultipleRequestHandler(
            new AddLabelToMultipleJSONDeserializer(
                new IriOfferIdentifierJSONDeserializer(
                    new IriOfferIdentifierFactory(
                        'https://io\.uitdatabank\.dev/(?<offertype>[event|place]+)/(?<offerid>[a-zA-Z0-9\-]+)'
                    )
                )
            ),
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_adding_label_to_multiple(): void
    {
        $addLabelToMultipleRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray([
                'offers' => [
                    [
                        '@id' => 'https://io.uitdatabank.dev/event/' . self::EVENT_ID,
                        '@type' => 'Event',
                    ],
                    [
                        '@id' => 'https://io.uitdatabank.dev/place/' . self::PLACE_ID,
                        '@type' => 'Place',
                    ],
                ],
                'label' => 'Dansje',
            ])
            ->build('POST');

        $response = $this->addLabelToMultipleRequestHandler->handle($addLabelToMultipleRequest);

        $this->assertEquals(
            [
                new AddLabelToMultiple(
                    OfferIdentifierCollection::fromArray(
                        [
                            new IriOfferIdentifier(
                                new Url('https://io.uitdatabank.dev/event/' . self::EVENT_ID),
                                self::EVENT_ID,
                                OfferType::event()
                            ),
                            new IriOfferIdentifier(
                                new Url('https://io.uitdatabank.dev/place/' . self::PLACE_ID),
                                self::PLACE_ID,
                                OfferType::place()
                            ),
                        ],
                    ),
                    new Label(
                        new LabelName('Dansje')
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new JsonResponse(['commandId' => Uuid::NIL]),
            $response
        );
    }
}
