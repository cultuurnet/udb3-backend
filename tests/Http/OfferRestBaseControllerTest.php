<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
}
