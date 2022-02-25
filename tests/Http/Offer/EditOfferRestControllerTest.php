<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\DescriptionJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\PriceInfo\PriceInfoDataValidator;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Commands\RemoveLabel;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\Offer\ReadModel\MainLanguage\MainLanguageQueryInterface;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\PriceInfo\Tariff;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\PriceInfo\PriceInfoJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\TitleJSONDeserializer;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use CultuurNet\UDB3\StringLiteral;

class EditOfferRestControllerTest extends TestCase
{
    /**
     * @var TraceableCommandBus
     */
    private $commandBus;

    /**
     * @var OfferEditingServiceInterface|MockObject
     */
    private $editService;

    /**
     * @var MainLanguageQueryInterface|MockObject
     */
    private $mainLanguageQuery;

    /**
     * @var LabelJSONDeserializer
     */
    private $labelDeserializer;

    /**
     * @var TitleJSONDeserializer
     */
    private $titleDeserializer;

    /**
     * @var DescriptionJSONDeserializer
     */
    private $descriptionDeserializer;

    /**
     * @var PriceInfoJSONDeserializer
     */
    private $priceInfoDeserializer;

    /**
     * @var DataValidatorInterface|MockObject
     */
    private $calendarDataValidator;

    /**
     * @var EditOfferRestController
     */
    private $controller;

    public function setUp()
    {
        $this->commandBus = new TraceableCommandBus();
        $this->editService = $this->createMock(OfferEditingServiceInterface::class);

        $this->mainLanguageQuery = $this->createMock(MainLanguageQueryInterface::class);

        $this->calendarDataValidator = $this->createMock(DataValidatorInterface::class);

        $this->labelDeserializer = new LabelJSONDeserializer();
        $this->titleDeserializer = new TitleJSONDeserializer();
        $this->descriptionDeserializer = new DescriptionJSONDeserializer();
        $this->priceInfoDeserializer = new PriceInfoJSONDeserializer(new PriceInfoDataValidator());

        $this->controller = new EditOfferRestController(
            $this->commandBus,
            $this->editService,
            $this->mainLanguageQuery,
            $this->labelDeserializer,
            $this->titleDeserializer,
            $this->descriptionDeserializer,
            $this->priceInfoDeserializer
        );
    }

    /**
     * @test
     */
    public function it_adds_a_label()
    {
        $label = 'test label';
        $cdbid = 'c6ff4c27-bdbb-452f-a1b5-d9e2e3aa846c';

        $this->commandBus->record();

        $response = $this->controller
            ->addLabel($cdbid, $label);

        $this->assertEquals([new AddLabel($cdbid, new Label($label))], $this->commandBus->getRecordedCommands());

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_adds_label_through_json()
    {
        $data = '{
            "label": "Bio",
            "offers": [
                {
                    "@id": "http://culudb-silex.dev:8080/event/0823f57e-a6bd-450a-b4f5-8459b4b11043",
                    "@type": "Event"
                }
            ]
        }';

        $cdbid = 'c6ff4c27-bdbb-452f-a1b5-d9e2e3aa846c';

        $request = new Request([], [], [], [], [], [], $data);

        $json = new StringLiteral($data);
        $expectedLabel = $this->labelDeserializer->deserialize($json);

        $this->commandBus->record();

        $response = $this->controller
            ->addLabelFromJsonBody($request, $cdbid);

        $this->assertEquals([new AddLabel($cdbid, $expectedLabel)], $this->commandBus->getRecordedCommands());

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_removes_a_label()
    {
        $label = 'test label';
        $cdbid = 'c6ff4c27-bdbb-452f-a1b5-d9e2e3aa846c';

        $this->commandBus->record();

        $response = $this->controller
            ->removeLabel($cdbid, $label);

        $this->assertEquals([new RemoveLabel($cdbid, new Label($label))], $this->commandBus->getRecordedCommands());

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_price_info()
    {
        $data = '[
            {"category": "base", "price": 15, "priceCurrency": "EUR"},
            {"category": "tarrif", "name": {"nl": "Werkloze dodo kwekers"}, "price": 0, "priceCurrency": "EUR"}
        ]';

        $cdbid = 'c6ff4c27-bdbb-452f-a1b5-d9e2e3aa846c';

        $this->mainLanguageQuery->expects($this->once())
            ->method('execute')
            ->with($cdbid)
            ->willReturn(new Language('nl'));

        $request = new Request([], [], [], [], [], [], $data);

        $expectedBasePrice = new BasePrice(
            new Money(1500, new Currency('EUR'))
        );

        $expectedTariff = new Tariff(
            new MultilingualString(new Language('nl'), new StringLiteral('Werkloze dodo kwekers')),
            new Money(0, new Currency('EUR'))
        );

        $expectedPriceInfo = (new PriceInfo($expectedBasePrice))
            ->withExtraTariff($expectedTariff);

        $this->editService->expects($this->once())
            ->method('updatePriceInfo')
            ->with(
                $cdbid,
                $expectedPriceInfo
            );

        $response = $this->controller
            ->updatePriceInfo($request, $cdbid);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_the_offer_description_by_lang()
    {
        $descriptionData = '{"description": "nieuwe beschrijving"}';

        $request = new Request([], [], [], [], [], [], $descriptionData);

        $this->editService->expects($this->once())
            ->method('updateDescription')
            ->with(
                'EC545F35-C76E-4EFC-8AB0-5024DA866CA0',
                new Language('nl'),
                new Description('nieuwe beschrijving')
            );

        $response = $this->controller
            ->updateDescription($request, 'EC545F35-C76E-4EFC-8AB0-5024DA866CA0', 'nl');

        $this->assertEquals(204, $response->getStatusCode());
    }
}
