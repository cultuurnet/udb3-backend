<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\DescriptionJSONDeserializer;
use CultuurNet\UDB3\Label as LegacyLabel;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Commands\RemoveLabel;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\Offer\ReadModel\MainLanguage\MainLanguageQueryInterface;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\TitleJSONDeserializer;
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

        $this->controller = new EditOfferRestController(
            $this->commandBus,
            $this->editService,
            $this->mainLanguageQuery,
            $this->labelDeserializer,
            $this->titleDeserializer,
            $this->descriptionDeserializer
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

        $this->assertEquals([new AddLabel($cdbid, new LegacyLabel($label))], $this->commandBus->getRecordedCommands());

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

        $this->assertEquals([new RemoveLabel($cdbid, $label)], $this->commandBus->getRecordedCommands());

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
