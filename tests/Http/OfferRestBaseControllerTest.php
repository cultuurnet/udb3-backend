<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use CultuurNet\UDB3\StringLiteral;

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

    protected function setUp(): void
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
