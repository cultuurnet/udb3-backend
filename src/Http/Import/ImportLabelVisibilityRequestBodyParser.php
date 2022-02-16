<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Import;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use Psr\Http\Message\ServerRequestInterface;
use CultuurNet\UDB3\StringLiteral;

/**
 * Manipulates the `labels` and `hiddenLabels` properties on JSON of events, places and organizers that need to be
 * imported, to make sure their visibility is set correctly.
 */
final class ImportLabelVisibilityRequestBodyParser implements RequestBodyParser
{
    private LabelRepository $labelsRepository;
    private LabelRelationsRepository $labelRelationsRepository;

    public function __construct(LabelRepository $labelsRepository, LabelRelationsRepository $labelRelationsRepository)
    {
        $this->labelsRepository = $labelsRepository;
        $this->labelRelationsRepository = $labelRelationsRepository;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $json = $request->getParsedBody();

        if (!is_object($json) || !isset($json->{'@id'})) {
            // Something went wrong in an earlier step, but this should be handled by the validation layer.
            return $request;
        }

        // Get all labels from the JSON to import
        $labels = isset($json->labels) && is_array($json->labels) ? $json->labels : [];
        $hiddenLabels = isset($json->hiddenLabels) && is_array($json->hiddenLabels) ? $json->hiddenLabels : [];

        // Combine all labels to import into a single array, and remove anything that is not a string to prevent type
        // errors down the line (should be caught by another request body parser).
        $importLabels = array_merge($labels, $hiddenLabels);
        $importLabels = array_filter($importLabels, fn ($label) => is_string($label));

        // Stop if there are no labels to import.
        if (empty($importLabels)) {
            return $request;
        }

        // Get all labels that are already on the resource and which were not imported (thus added via the UI usually).
        $idParts = explode('/', $json->{'@id'});
        $id = array_pop($idParts);

        $uiLabels = array_map(
            fn (LabelRelation $labelRelation) => $labelRelation->getLabelName()->toNative(),
            array_filter(
                $this->labelRelationsRepository->getLabelRelationsForItem(new StringLiteral($id)),
                fn (LabelRelation $labelRelation) => !$labelRelation->isImported()
            )
        );

        // Add the UI labels to the labels to import so they do not get removed by importers who don't take check that
        // new labels have been added before sending an update. Remove any duplicates. Use array_values() to prevent
        // gaps in the keys, which would make it an associative array instead of a sequential array.
        $importLabels = array_values(array_unique(array_merge($importLabels, $uiLabels)));

        // Reset the label properties on the JSON and add each label to the correct one.
        $json->labels = [];
        $json->hiddenLabels = [];
        foreach ($importLabels as $importLabel) {
            $label = $this->labelsRepository->getByName(new StringLiteral($importLabel));
            $visibility = $label ? $label->getVisibility() : Visibility::VISIBLE();

            if ($visibility->sameAs(Visibility::VISIBLE())) {
                $json->labels[] = $importLabel;
            } else {
                $json->hiddenLabels[] = $importLabel;
            }
        }

        if (empty($json->labels)) {
            unset($json->labels);
        }
        if (empty($json->hiddenLabels)) {
            unset($json->hiddenLabels);
        }

        return $request->withParsedBody($json);
    }
}
