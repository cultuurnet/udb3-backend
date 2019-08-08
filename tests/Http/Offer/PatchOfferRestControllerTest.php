<?php


namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Event\Commands\Moderation\Approve;
use CultuurNet\UDB3\Event\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Event\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish;
use CultuurNet\UDB3\Event\Commands\Moderation\Reject;
use CultuurNet\UDB3\Place\Commands\Moderation\Approve as ApprovePlace;
use CultuurNet\UDB3\Place\Commands\Moderation\FlagAsDuplicate as FlagAsDuplicatePlace;
use CultuurNet\UDB3\Place\Commands\Moderation\FlagAsInappropriate as FlagAsInappropriatePlace;
use CultuurNet\UDB3\Place\Commands\Moderation\Reject as RejectPlace;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Offer\OfferType;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\StringLiteral\StringLiteral;

class PatchOfferRestControllerTest extends TestCase
{
    /**
     * @var CommandBusInterface | PHPUnit_Framework_MockObject_MockObject
     */
    private $commandBus;

    /**
     * @var string
     */
    private $itemId = 'e6238239-4ec1-4778-a0ca-bf7fb0256eed';

    public function setUp()
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
    }

    /**
     * @test
     * @dataProvider commandRequestDataProvider
     * @param OfferType $offerType
     * @param Request $request
     * @param AbstractCommand $expectedCommand
     */
    public function it_should_dispatch_the_requested_offer_commands(
        OfferType $offerType,
        Request $request,
        AbstractCommand $expectedCommand
    ) {
        $controller = new PatchOfferRestController($offerType, $this->commandBus);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand)
            ->willReturn('6a9762dc-f0d6-400d-b097-00ada39a76e2');

        $response = $controller->handle($request, $this->itemId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function commandRequestDataProvider()
    {
        return [
            'Approve event' => [
                'offerType' => OfferType::EVENT(),
                'request' => $this->generatePatchRequest('application/ld+json;domain-model=Approve'),
                'expectedCommand' => new Approve($this->itemId)
            ],
            'Reject event' => [
                'offerType' => OfferType::EVENT(),
                'request' => $this->generatePatchRequest(
                    'application/ld+json;domain-model=Reject',
                    json_encode(['reason' => 'Description missing :('])
                ),
                'expectedCommand' => new Reject($this->itemId, new StringLiteral('Description missing :('))
            ],
            'Flag event as duplicate' => [
                'offerType' => OfferType::EVENT(),
                'request' => $this->generatePatchRequest('application/ld+json;domain-model=FlagAsDuplicate'),
                'expectedCommand' => new FlagAsDuplicate($this->itemId)
            ],
            'Flag event as inappropriate' => [
                'offerType' => OfferType::EVENT(),
                'request' => $this->generatePatchRequest('application/ld+json;domain-model=FlagAsInappropriate'),
                'expectedCommand' => new FlagAsInappropriate($this->itemId)
            ],
            'Approve place' => [
                'offerType' => OfferType::PLACE(),
                'request' => $this->generatePatchRequest('application/ld+json;domain-model=Approve'),
                'expectedCommand' => new ApprovePlace($this->itemId)
            ],
            'Reject place' => [
                'offerType' => OfferType::PLACE(),
                'request' => $this->generatePatchRequest(
                    'application/ld+json;domain-model=Reject',
                    json_encode(['reason' => 'Description missing :('])
                ),
                'expectedCommand' => new RejectPlace($this->itemId, new StringLiteral('Description missing :('))
            ],
            'Flag place as duplicate' => [
                'offerType' => OfferType::PLACE(),
                'request' => $this->generatePatchRequest('application/ld+json;domain-model=FlagAsDuplicate'),
                'expectedCommand' => new FlagAsDuplicatePlace($this->itemId)
            ],
            'Flag place as inappropriate' => [
                'offerType' => OfferType::PLACE(),
                'request' => $this->generatePatchRequest('application/ld+json;domain-model=FlagAsInappropriate'),
                'expectedCommand' => new FlagAsInappropriatePlace($this->itemId)
            ],
            'Publish event with publication date' => [
                'offerType' => OfferType::EVENT(),
                'request' => $this->generatePatchRequest(
                    'application/ld+json;domain-model=Publish',
                    json_encode(['publicationDate' => '2017-02-01T12:00:00+00:00'])
                ),
                'expectedCommand' => new Publish(
                    $this->itemId,
                    \DateTime::createFromFormat(
                        \DateTime::ISO8601,
                        '2017-02-01T12:00:00+00:00'
                    )
                )
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_publication_date_format()
    {
        $controller = new PatchOfferRestController(OfferType::EVENT(), $this->commandBus);

        $request = $this->generatePatchRequest(
            'application/ld+json;domain-model=Publish',
            json_encode(['publicationDate' => '2017/02/01T12'])
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The publication date is not a valid date format.');

        $controller->handle($request, $this->itemId);
    }

    /**
     * @test
     */
    public function it_has_a_default_publication_date_of_now()
    {
        $controller = new PatchOfferRestController(OfferType::EVENT(), $this->commandBus);

        $request = $this->generatePatchRequest(
            'application/ld+json;domain-model=Publish'
        );

        $beforeDate = new \DateTime();

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (Publish $command) use ($beforeDate) {
                $afterDate = new \DateTime();
                $this->assertEquals($command->getItemId(), $this->itemId);
                $this->assertGreaterThanOrEqual(
                    $beforeDate,
                    $command->getPublicationDate()
                );
                $this->assertLessThanOrEqual(
                    $afterDate,
                    $command->getPublicationDate()
                );
                return '6a9762dc-f0d6-400d-b097-00ada39a76e2';
            });

        $response = $controller->handle($request, $this->itemId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @param string $contentType
     * @param $content
     * @return Request
     */
    private function generatePatchRequest($contentType, $content = null)
    {
        $request = Request::create('/offer/' . $this->itemId, 'PATCH', [], [], [], [], $content);
        $request->headers->set('Content-Type', $contentType);

        return $request;
    }
}
