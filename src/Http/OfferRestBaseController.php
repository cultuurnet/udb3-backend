<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\EventEditingServiceInterface;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\Place\PlaceEditingServiceInterface;
use CultuurNet\UDB3\Http\Deserializer\BookingInfo\BookingInfoJSONDeserializer;
use CultuurNet\UDB3\HttpFoundation\Response\NoContent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CultuurNet\UDB3\StringLiteral;

class OfferRestBaseController
{
    /**
     * TODO: Create a shared interface for event and places
     * @var EventEditingServiceInterface|PlaceEditingServiceInterface|OfferEditingServiceInterface
     */
    protected $editor;

    /**
     * @var MediaManagerInterface
     */
    protected $mediaManager;

    /**
     * @var JSONDeserializer
     */
    private $bookingInfoDeserializer;

    /**
     * @param EventEditingServiceInterface|PlaceEditingServiceInterface $editor
     * @param ?JSONDeserializer $bookingInfoDeserializer
     */
    public function __construct(
        $editor,
        MediaManagerInterface $mediaManager,
        JSONDeserializer $bookingInfoDeserializer = null
    ) {
        $this->editor = $editor;
        $this->mediaManager = $mediaManager;

        if (!$bookingInfoDeserializer) {
            $bookingInfoDeserializer = new BookingInfoJSONDeserializer();
        }
        $this->bookingInfoDeserializer = $bookingInfoDeserializer;
    }

    /**
     * @deprecated
     */
    public function updateOrganizerFromJsonBody(Request $request, string $cdbid): Response
    {
        $bodyContent = json_decode($request->getContent());

        // @todo Use a data validator and change to an exception so it can be converted to an API problem
        if (empty($bodyContent->organizer)) {
            return new JsonResponse(['error' => 'organizer required'], 400);
        }

        $this->editor->updateOrganizer($cdbid, $bodyContent->organizer);

        return new NoContent();
    }

    public function deleteOrganizer(string $cdbid, string $organizerId): Response
    {
        $this->editor->deleteOrganizer($cdbid, $organizerId);

        return new NoContent();
    }

    public function addImage(Request $request, string $itemId): Response
    {
        $bodyContent = json_decode($request->getContent());
        if (empty($bodyContent->mediaObjectId)) {
            return new JsonResponse(['error' => 'media object id required'], 400);
        }

        // @todo Validate that this id exists and is in fact an image and not a different type of media object
        $imageId = new UUID($bodyContent->mediaObjectId);

        $this->editor->addImage($itemId, $imageId);

        return new NoContent();
    }

    public function selectMainImage(Request $request, string $itemId): Response
    {
        $bodyContent = json_decode($request->getContent());
        if (empty($bodyContent->mediaObjectId)) {
            return new JsonResponse(['error' => 'media object id required'], 400);
        }

        $mediaObjectId = new UUID($bodyContent->mediaObjectId);

        // Can we be sure that the given $mediaObjectId points to an image and not a different type?
        $image = $this->mediaManager->getImage($mediaObjectId);

        $this->editor->selectMainImage($itemId, $image);

        return new NoContent();
    }

    public function updateImage(Request $request, string $itemId, string $mediaObjectId): Response
    {
        $bodyContent = json_decode($request->getContent());
        $description = new StringLiteral($bodyContent->description);
        $copyrightHolder = new CopyrightHolder($bodyContent->copyrightHolder);
        $imageId = new UUID($mediaObjectId);

        // Can we be sure that the given $mediaObjectId points to an image and not a different type?
        $image = $this->mediaManager->getImage($imageId);

        $this->editor->updateImage(
            $itemId,
            $image,
            $description,
            $copyrightHolder
        );

        return new NoContent();
    }

    public function removeImage(string $itemId, string $mediaObjectId): Response
    {
        $imageId = new UUID($mediaObjectId);

        // Can we be sure that the given $mediaObjectId points to an image and not a different type?
        $image = $this->mediaManager->getImage($imageId);

        $this->editor->removeImage($itemId, $image);

        return new NoContent();
    }
}
