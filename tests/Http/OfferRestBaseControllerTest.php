<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url as LegacyUrl;

class OfferRestBaseControllerTest extends TestCase
{
    /**
     * @var OfferEditingServiceInterface|MockObject
     */
    private $offerEditingService;

    /**
     * @var MediaManagerInterface|MockObject
     */
    private $mediaManager;

    /**
     * @var OfferRestBaseController|MockObject
     */
    private $offerRestBaseController;

    protected function setUp()
    {
        $this->offerEditingService = $this->createMock(
            OfferEditingServiceInterface::class
        );

        $this->mediaManager = $this->createMock(
            MediaManagerInterface::class
        );

        $this->offerRestBaseController = $this->getMockForAbstractClass(
            OfferRestBaseController::class,
            [
                $this->offerEditingService,
                $this->mediaManager,
            ]
        );
    }

    /**
     * @test
     */
    public function it_can_update_typical_age_range()
    {
        $cdbid = 'f636ae50-ac26-48f0-ac1f-929e361ae403';
        $content = '{"typicalAgeRange":"2-12"}';
        $request = new Request([], [], [], [], [], [], $content);

        $this->offerEditingService
            ->expects($this->once())
            ->method('updateTypicalAgeRange')
            ->with(
                $cdbid,
                AgeRange::fromString('2-12')
            );

        $this->offerRestBaseController->updateTypicalAgeRange(
            $request,
            $cdbid
        );
    }

    /**
     * @test
     */
    public function it_should_update_an_offer_organization()
    {
        $this->offerEditingService
            ->expects($this->once())
            ->method('updateOrganizer')
            ->with(
                '301A7905-D329-49DD-8F2F-19CE6C3C10D4',
                '28AB9364-D650-4C6A-BCF5-E918A49025DF'
            );

        $response = $this->offerRestBaseController->updateOrganizer(
            '301A7905-D329-49DD-8F2F-19CE6C3C10D4',
            '28AB9364-D650-4C6A-BCF5-E918A49025DF'
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_should_update_booking_info()
    {
        $givenOfferId = 'b125e7b8-08ac-4740-80e1-b502ff716048';
        $givenJson = json_encode(
            [
                'bookingInfo' => [
                    'url' => 'https://publiq.be',
                    'urlLabel' => ['nl' => 'Publiq vzw'],
                    'phone' => '044/444444',
                    'email' => 'info@publiq.be',
                    'availabilityStarts' => '2018-01-01T00:00:00+01:00',
                    'availabilityEnds' => '2018-01-31T23:59:59+01:00',
                ],
            ]
        );

        $givenRequest = new Request([], [], [], [], [], [], $givenJson);

        $this->offerEditingService->expects($this->once())
            ->method('updateBookingInfo')
            ->with(
                $givenOfferId,
                new BookingInfo(
                    'https://publiq.be',
                    new MultilingualString(new Language('nl'), new StringLiteral('Publiq vzw')),
                    '044/444444',
                    'info@publiq.be',
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T00:00:00+01:00'),
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-31T23:59:59+01:00')
                )
            );

        $response = $this->offerRestBaseController->updateBookingInfo($givenRequest, $givenOfferId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_should_update_an_image(): void
    {
        $offerId = '2b0682a4-c751-4440-81c5-3739965f2cea';
        $imageId = '0d24c18a-0bd5-46c1-b331-1fa38012bded';
        $json = json_encode(
            [
                'description' => 'new description',
                'copyrightHolder' => 'new copyrightholder',
            ]
        );

        $image = new Image(
            new UUID($imageId),
            MIMEType::fromSubtype('jpeg'),
            new Description('description'),
            new CopyrightHolder('copyrightholder'),
            new Url('https://url.com/image.jpeg'),
            new Language('nl')
        );

        $this->mediaManager
            ->method('getImage')
            ->with(new UUID($imageId))
            ->willReturn($image);

        $this->offerEditingService
            ->method('updateImage')
            ->with(
                $offerId,
                $image,
                new StringLiteral('new description'),
                new CopyrightHolder('new copyrightholder')
            );

        $response = $this->offerRestBaseController->updateImage(
            new Request([], [], [], [], [], [], $json),
            $offerId,
            $imageId
        );

        $this->assertEquals(204, $response->getStatusCode());
    }
}
