<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Offer\Commands\AbstractLabelCommand;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use PHPUnit\Framework\TestCase;

final class AddLabelFromJsonBodyRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const LABEL_NAME = 'Some new Label';

    private TraceableCommandBus $commandBus;

    private AddLabelFromJsonBodyRequestHandler $addLabelFromJsonBodyRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->addLabelFromJsonBodyRequestHandler = new AddLabelFromJsonBodyRequestHandler(
            $this->commandBus,
            new LabelJSONDeserializer()
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_adding_a_label_to_an_offer(
        string $offerType,
        string $type,
        AbstractLabelCommand $addLabel
    ): void {
        $addLabelFromJsonBodyRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray([
                'label' => self::LABEL_NAME,
                'offers' => [
                    0 => [
                        '@id' => 'http://culudb-silex.dev:8080/event/' . self::OFFER_ID,
                        '@type' => $type,
                    ],
                ],
            ])
            ->build('POST');

        $response = $this->addLabelFromJsonBodyRequestHandler->handle($addLabelFromJsonBodyRequest);

        $this->assertEquals(
            [
                $addLabel,
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
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_on_invalid_label(string $offerType, string $type): void
    {
        $addLabelFromJsonBodyRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray([
                'label' => 'Geen;geldig;label;',
                'offers' => [
                    0 => [
                        '@id' => 'http://culudb-silex.dev:8080/event/' . self::OFFER_ID,
                        '@type' => $type,
                    ],
                ],
            ])
            ->withRouteParameter('labelName', 'Geen;geldig;label;')
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('The label should match pattern: ^[^;]{2,255}$'),
            fn () => $this->addLabelFromJsonBodyRequestHandler->handle($addLabelFromJsonBodyRequest)
        );
    }

    public function offerTypeDataProvider(): array
    {
        return [
            [
                'offerType' => 'events',
                'type' => 'Event',
                'addLabel' => new AddLabel(
                    self::OFFER_ID,
                    new Label(new LabelName(self::LABEL_NAME))
                ),
            ],
            [
                'offerType' => 'places',
                'type' => 'Place',
                'addLabel' => new AddLabel(
                    self::OFFER_ID,
                    new Label(new LabelName(self::LABEL_NAME))
                ),
            ],
        ];
    }
}
