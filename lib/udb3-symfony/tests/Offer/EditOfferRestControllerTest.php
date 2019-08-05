<?php

namespace CultuurNet\UDB3\Symfony\Offer;

use CultuurNet\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\DescriptionJSONDeserializer;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\Offer\ReadModel\MainLanguage\MainLanguageQueryInterface;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\PriceInfo\Tariff;
use CultuurNet\UDB3\Symfony\Deserializer\Calendar\CalendarJSONDeserializer;
use CultuurNet\UDB3\Symfony\Deserializer\Calendar\CalendarJSONParser;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Symfony\Deserializer\PriceInfo\PriceInfoJSONDeserializer;
use CultuurNet\UDB3\Symfony\Deserializer\TitleJSONDeserializer;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;

class EditOfferRestControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OfferEditingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $editService;

    /**
     * @var MainLanguageQueryInterface|\PHPUnit_Framework_MockObject_MockObject
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
     * @var CalendarJSONDeserializer
     */
    private $calendarDeserializer;

    /**
     * @var DataValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $calendarDataValidator;

    /**
     * @var DeserializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $facilitiesJSONDeserializer;

    /**
     * @var EditOfferRestController
     */
    private $controller;

    public function setUp()
    {
        $this->editService = $this->createMock(OfferEditingServiceInterface::class);

        $this->mainLanguageQuery = $this->createMock(MainLanguageQueryInterface::class);

        $this->calendarDataValidator = $this->createMock(DataValidatorInterface::class);

        $this->labelDeserializer = new LabelJSONDeserializer();
        $this->titleDeserializer = new TitleJSONDeserializer();
        $this->descriptionDeserializer = new DescriptionJSONDeserializer();
        $this->priceInfoDeserializer = new PriceInfoJSONDeserializer();
        $this->calendarDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            $this->calendarDataValidator
        );
        $this->facilitiesJSONDeserializer = $this->createMock(DeserializerInterface::class);

        $this->controller = new EditOfferRestController(
            $this->editService,
            $this->mainLanguageQuery,
            $this->labelDeserializer,
            $this->titleDeserializer,
            $this->descriptionDeserializer,
            $this->priceInfoDeserializer,
            $this->calendarDeserializer,
            $this->facilitiesJSONDeserializer
        );
    }

    /**
     * @test
     */
    public function it_adds_a_label()
    {
        $label = 'test label';
        $cdbid = 'c6ff4c27-bdbb-452f-a1b5-d9e2e3aa846c';

        $this->editService->expects($this->once())
            ->method('addLabel')
            ->with(
                $cdbid,
                $label
            );

        $response = $this->controller
            ->addLabel($cdbid, $label);

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

        $this->editService->expects($this->once())
            ->method('addLabel')
            ->with(
                $cdbid,
                $expectedLabel
            );

        $response = $this->controller
            ->addLabelFromJsonBody($request, $cdbid);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_removes_a_label()
    {
        $label = 'test label';
        $cdbid = 'c6ff4c27-bdbb-452f-a1b5-d9e2e3aa846c';

        $this->editService->expects($this->once())
            ->method('removeLabel')
            ->with(
                $cdbid,
                $label
            );

        $response = $this->controller
            ->removeLabel($cdbid, $label);

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
            new Price(1500),
            Currency::fromNative('EUR')
        );

        $expectedTariff = new Tariff(
            new MultilingualString(new Language('nl'), new StringLiteral('Werkloze dodo kwekers')),
            new Price(0),
            Currency::fromNative('EUR')
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

    /**
     * @test
     */
    public function it_updates_the_calendar_info()
    {
        $eventId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $calendar = new Calendar(
            CalendarType::PERMANENT()
        );

        $calendarData = '{"calendarType": "permanent"}';

        $request = new Request([], [], [], [], [], [], $calendarData);

        $this->editService->expects($this->once())
            ->method('updateCalendar')
            ->with(
                $eventId,
                $calendar
            );

        $response = $this->controller
            ->updateCalendar($request, $eventId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_the_theme_by_id()
    {
        $this->editService
            ->expects($this->once())
            ->method('updateTheme')
            ->with(
                'B904CD9E-0125-473E-ADDB-EC5E7ED12875',
                new StringLiteral('CEFFE9F0-AD3C-446B-838A-0E309843C5E1')
            )
            ->willReturn('EBFF0B3A-0401-4C4D-A355-D326C8A4F31A');

        $response = $this->controller
            ->updateTheme(
                'B904CD9E-0125-473E-ADDB-EC5E7ED12875',
                'CEFFE9F0-AD3C-446B-838A-0E309843C5E1'
            );

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_the_type_by_id()
    {
        $this->editService
            ->expects($this->once())
            ->method('updateType')
            ->with(
                'BA403978-7378-41F7-A416-C5D2155D6EDE',
                new StringLiteral('6B22AC5E-83AF-4590-91C9-91B4D66426CD')
            );

        $response = $this->controller
            ->updateType(
                'BA403978-7378-41F7-A416-C5D2155D6EDE',
                '6B22AC5E-83AF-4590-91C9-91B4D66426CD'
            );

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_the_facilities_of_a_place()
    {
        $json = json_encode(
            [
                'facilities' =>
                    [
                        '3.23.1.0.0',
                        '3.23.2.0.0',
                        '3.23.3.0.0'
                    ]
            ]
        );

        $facilities = [
            new Facility('3.23.1.0.0', 'Voorzieningen voor rolstoelgebruikers'),
            new Facility('3.23.2.0.0', 'Assistentie'),
            new Facility('3.23.3.0.0', 'Rolstoel ter beschikking'),
        ];

        $request = new Request([], [], [], [], [], [], $json);

        $placeId = '6645274f-d969-4d70-865e-3ec799db9624';

        $this->facilitiesJSONDeserializer->expects($this->once())
            ->method('deserialize')
            ->with(new StringLiteral($json))
            ->willReturn($facilities);

        $this->editService->expects($this->once())
            ->method('updateFacilities')
            ->with(
                $placeId,
                $facilities
            );

        $response = $this->controller->updateFacilities($request, $placeId);

        $this->assertEquals(204, $response->getStatusCode());
    }
}
