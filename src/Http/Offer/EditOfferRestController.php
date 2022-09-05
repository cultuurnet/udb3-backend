<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\Offer\ReadModel\MainLanguage\MainLanguageQueryInterface;
use CultuurNet\UDB3\HttpFoundation\Response\NoContent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CultuurNet\UDB3\StringLiteral;

class EditOfferRestController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    private OfferEditingServiceInterface $editService;

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

    public function __construct(
        CommandBus $commandBus,
        OfferEditingServiceInterface $editingServiceInterface,
        MainLanguageQueryInterface $mainLanguageQuery,
        DeserializerInterface $labelJsonDeserializer,
        DeserializerInterface $titleJsonDeserializer,
        DeserializerInterface $descriptionJsonDeserializer
    ) {
        $this->commandBus = $commandBus;
        $this->editService = $editingServiceInterface;
        $this->mainLanguageQuery = $mainLanguageQuery;
        $this->labelJsonDeserializer = $labelJsonDeserializer;
        $this->titleJsonDeserializer = $titleJsonDeserializer;
        $this->descriptionJsonDeserializer = $descriptionJsonDeserializer;
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
}
