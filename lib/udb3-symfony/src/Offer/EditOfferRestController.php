<?php

namespace CultuurNet\UDB3\Symfony\Offer;

use CultuurNet\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\Offer\ReadModel\MainLanguage\MainLanguageQueryInterface;
use CultuurNet\UDB3\Symfony\Deserializer\PriceInfo\PriceInfoJSONDeserializer;
use CultuurNet\UDB3\Symfony\HttpFoundation\NoContent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\StringLiteral\StringLiteral;

class EditOfferRestController
{
    /**
     * @var OfferEditingServiceInterface
     */
    private $editService;

    /**
     * @var MainLanguageQueryInterface
     */
    private $mainLanguageQuery;

    /**
     * @var DeserializerInterface
     */
    private $labelJsonDeserializer;

    /**
     * @var DeserializerInterface
     */
    private $titleJsonDeserializer;

    /**
     * @var DeserializerInterface
     */
    private $descriptionJsonDeserializer;

    /**
     * @var DeserializerInterface
     */
    private $priceInfoJsonDeserializer;

    /**
     * @var DeserializerInterface
     */
    private $calendarJsonDeserializer;

    /**
     * @var DeserializerInterface
     */
    private $facilityDeserializer;

    /**
     * EditOfferRestController constructor.
     * @param OfferEditingServiceInterface $editingServiceInterface
     * @param MainLanguageQueryInterface $mainLanguageQuery
     * @param DeserializerInterface $labelJsonDeserializer
     * @param DeserializerInterface $titleJsonDeserializer
     * @param DeserializerInterface $descriptionJsonDeserializer
     * @param DeserializerInterface $priceInfoJsonDeserializer
     * @param DeserializerInterface $calendarJsonDeserializer
     * @param DeserializerInterface $facilityDeserializer
     */
    public function __construct(
        OfferEditingServiceInterface $editingServiceInterface,
        MainLanguageQueryInterface $mainLanguageQuery,
        DeserializerInterface $labelJsonDeserializer,
        DeserializerInterface $titleJsonDeserializer,
        DeserializerInterface $descriptionJsonDeserializer,
        DeserializerInterface $priceInfoJsonDeserializer,
        DeserializerInterface $calendarJsonDeserializer,
        DeserializerInterface $facilityDeserializer
    ) {
        $this->editService = $editingServiceInterface;
        $this->mainLanguageQuery = $mainLanguageQuery;
        $this->labelJsonDeserializer = $labelJsonDeserializer;
        $this->titleJsonDeserializer = $titleJsonDeserializer;
        $this->descriptionJsonDeserializer = $descriptionJsonDeserializer;
        $this->priceInfoJsonDeserializer = $priceInfoJsonDeserializer;
        $this->calendarJsonDeserializer = $calendarJsonDeserializer;
        $this->facilityDeserializer = $facilityDeserializer;
    }

    public function addLabel(string $cdbid, string $label): Response
    {
        $this->editService->addLabel(
            $cdbid,
            new Label($label)
        );

        return new NoContent();
    }

    /**
     * @deprecated
     */
    public function addLabelFromJsonBody(Request $request, string $cdbid): Response
    {
        $json = new StringLiteral($request->getContent());
        $label = $this->labelJsonDeserializer->deserialize($json);

        $this->editService->addLabel(
            $cdbid,
            $label
        );

        return new NoContent();
    }

    public function removeLabel(string $cdbid, string $label): Response
    {
        $this->editService->removeLabel(
            $cdbid,
            new Label($label)
        );

        return new NoContent();
    }

    public function updateTitle(Request $request, string $cdbid, string $lang): Response
    {
        $title = $this->titleJsonDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->editService->updateTitle(
            $cdbid,
            new Language($lang),
            $title
        );

        return new NoContent();
    }

    public function updateDescription(Request $request, $cdbid, $lang): Response
    {
        $description = $this->descriptionJsonDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->editService->updateDescription(
            $cdbid,
            new Language($lang),
            $description
        );

        return new NoContent();
    }

    public function updateType(string $cdbid, string $typeId): Response
    {
        $this->editService->updateType($cdbid, new StringLiteral($typeId));
        return new NoContent();
    }

    public function updateTheme(string $cdbid, string $themeId): Response
    {
        $this->editService->updateTheme($cdbid, new StringLiteral($themeId));
        return new NoContent();
    }

    public function updateFacilities(Request $request, string $cdbid): Response
    {
        $facilities = $this->facilityDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->editService->updateFacilities($cdbid, $facilities);

        return new NoContent();
    }

    public function updateCalendar(Request $request, string $cdbid): Response
    {
        $calendar = $this->calendarJsonDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->editService->updateCalendar(
            $cdbid,
            $calendar
        );

        return new NoContent();
    }

    public function updatePriceInfo(Request $request, string $cdbid): Response
    {
        $mainLanguage = null;
        $deserializer = $this->priceInfoJsonDeserializer;

        try {
            $mainLanguage = $this->mainLanguageQuery->execute($cdbid);
        } catch (EntityNotFoundException $e) {
            // Will be handled by the editService.
        }

        if ($mainLanguage && $deserializer instanceof PriceInfoJSONDeserializer) {
            $deserializer = $deserializer->forMainLanguage($mainLanguage);
        }

        $priceInfo = $deserializer->deserialize(new StringLiteral($request->getContent()));

        $this->editService->updatePriceInfo(
            $cdbid,
            $priceInfo
        );

        return new NoContent();
    }
}
