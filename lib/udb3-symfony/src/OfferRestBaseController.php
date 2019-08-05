<?php

namespace CultuurNet\UDB3\Symfony;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\EventEditingServiceInterface;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\Place\PlaceEditingServiceInterface;
use CultuurNet\UDB3\Symfony\Deserializer\BookingInfo\BookingInfoJSONDeserializer;
use CultuurNet\UDB3\Symfony\HttpFoundation\NoContent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Base class for offer reset callbacks.
 */
abstract class OfferRestBaseController
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
     * @var BookingInfoJSONDeserializer
     */
    private $bookingInfoDeserializer;

    /**
     * OfferRestBaseController constructor.
     * @param EventEditingServiceInterface|PlaceEditingServiceInterface $editor
     * @param MediaManagerInterface $mediaManager
     * @param JSONDeserializer $bookingInfoDeserializer
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

    public function updateTypicalAgeRange(Request $request, string $cdbid): Response
    {
        $body_content = json_decode($request->getContent());

        // @todo Use a data validator and change to an exception so it can be converted to an API problem
        if (empty($body_content->typicalAgeRange)) {
            return new JsonResponse(['error' => "typicalAgeRange required"], 400);
        }

        $ageRange = AgeRange::fromString($body_content->typicalAgeRange);

        $this->editor->updateTypicalAgeRange($cdbid, $ageRange);

        return new NoContent();
    }

    public function deleteTypicalAgeRange(string $cdbid)
    {
        $this->editor->deleteTypicalAgeRange($cdbid);

        return new NoContent();
    }

    public function updateOrganizer(string $cdbid, string $organizerId): Response
    {
        $this->editor->updateOrganizer($cdbid, $organizerId);

        return new NoContent();
    }

    /**
     * @deprecated
     */
    public function updateOrganizerFromJsonBody(Request $request, string $cdbid): Response
    {
        $body_content = json_decode($request->getContent());

        // @todo Use a data validator and change to an exception so it can be converted to an API problem
        if (empty($body_content->organizer)) {
            return new JsonResponse(['error' => "organizer required"], 400);
        }

        $this->editor->updateOrganizer($cdbid, $body_content->organizer);

        return new NoContent();
    }

    public function deleteOrganizer(string $cdbid, string $organizerId): Response
    {
        $this->editor->deleteOrganizer($cdbid, $organizerId);

        return new NoContent();
    }

    public function updateContactPoint(Request $request, string $cdbid): Response
    {
        $body_content = json_decode($request->getContent());

        // @todo Use a data validator and change to an exception so it can be converted to an API problem
        if (empty($body_content->contactPoint) ||
            !isset($body_content->contactPoint->url) ||
            !isset($body_content->contactPoint->email) ||
            !isset($body_content->contactPoint->phone)) {
            return new JsonResponse(['error' => "contactPoint and his properties required"], 400);
        }

        $this->editor->updateContactPoint(
            $cdbid,
            new ContactPoint(
                $body_content->contactPoint->phone,
                $body_content->contactPoint->email,
                $body_content->contactPoint->url
            )
        );

        return new NoContent();
    }

    public function updateBookingInfo(Request $request, string $cdbid): Response
    {
        $body = (string) $request->getContent();
        $bookingInfo = $this->bookingInfoDeserializer->deserialize(new StringLiteral($body));

        $this->editor->updateBookingInfo($cdbid, $bookingInfo);

        return new NoContent();
    }

    public function addImage(Request $request, string $itemId): Response
    {
        $body_content = json_decode($request->getContent());
        if (empty($body_content->mediaObjectId)) {
            return new JsonResponse(['error' => "media object id required"], 400);
        }

        // @todo Validate that this id exists and is in fact an image and not a different type of media object
        $imageId = new UUID($body_content->mediaObjectId);

        $this->editor->addImage($itemId, $imageId);

        return new NoContent();
    }

    public function selectMainImage(Request $request, string $itemId): Response
    {
        $body_content = json_decode($request->getContent());
        if (empty($body_content->mediaObjectId)) {
            return new JsonResponse(['error' => "media object id required"], 400);
        }

        $mediaObjectId = new UUID($body_content->mediaObjectId);

        // @todo MediaManagerInterface has no getImage() method.
        // Also, can we be sure that the given $mediaObjectId points to an image and not a different type?
        $image = $this->mediaManager->getImage($mediaObjectId);

        $this->editor->selectMainImage($itemId, $image);

        return new NoContent();
    }

    public function updateImage(Request $request, string $itemId, string $mediaObjectId): Response
    {
        $body_content = json_decode($request->getContent());
        $description = new StringLiteral($body_content->description);
        $copyrightHolder = new StringLiteral($body_content->copyrightHolder);
        $imageId = new UUID($mediaObjectId);

        // @todo MediaManagerInterface has no getImage() method.
        // Also, can we be sure that the given $mediaObjectId points to an image and not a different type?
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

        // @todo MediaManagerInterface has no getImage() method.
        // Also, can we be sure that the given $mediaObjectId points to an image and not a different type?
        $image = $this->mediaManager->getImage($imageId);

        $this->editor->removeImage($itemId, $image);

        return new NoContent();
    }
}
