<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Import;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use Psr\Http\Message\ServerRequestInterface;
use ValueObjects\StringLiteral\StringLiteral;

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
        if ((!isset($json->labels) || !is_array($json->labels)) &&
            (!isset($json->hiddenLabels) || !is_array($json->hiddenLabels))) {
            // No valid labels properties on the JSON so nothing to do here.
            return $request;
        }

        $idParts = explode('/', $json->{'@id'});
        $id = array_pop($idParts);

        // Approach is to:
        //  1. get all pre-existing UDB3 labels from label relation (hidden and visible)
        //  2. remove all pre-existing UDB3 labels from document (both hidden and visible)
        //  3. re-add all pre-existing UDB3 labels to document (both hidden and visible) with the correct visibility
        // By using this approach the correct visible/invisible label state
        // is taken into account and the JSON document is correct.

        //  1. get all pre-existing UDB3 labels from label relation (hidden and visible)
        /** @var LabelRelation[] $udb3LabelRelations */
        $udb3LabelRelations = array_filter(
            $this->labelRelationsRepository->getLabelRelationsForItem(
                new StringLiteral($id)
            ),
            function (LabelRelation $labelRelation) {
                return !$labelRelation->isImported();
            }
        );
        $udb3Labels = array_map(
            function (LabelRelation $labelRelation) {
                return $labelRelation->getLabelName()->toNative();
            },
            $udb3LabelRelations
        );

        //  2. remove all pre-existing UDB3 labels from document (both hidden and visible)
        //  Also take into account missing labels/hiddenLabels arrays by setting it to an empty array.
        $json->labels = array_diff($json->labels ?? [], $udb3Labels);
        $json->hiddenLabels = array_diff($json->hiddenLabels ?? [], $udb3Labels);

        //  3. re-add all pre-existing UDB3 labels to document (both hidden and visible) with the correct visibility
        foreach ($udb3LabelRelations as $udb3LabelRelation) {
            $name = $udb3LabelRelation->getLabelName();
            $label = $this->labelsRepository->getByName($name);
            $visibility = Visibility::VISIBLE();
            if ($label !== null) {
                $visibility = $label->getVisibility();
            }

            if ($visibility->sameValueAs(Visibility::VISIBLE())) {
                $json->labels[] = $name->toNative();
            } else {
                $json->hiddenLabels[] = $name->toNative();
            }
        }

        // Remove any duplicates and make sure the labels are always arrays.
        $json->labels = array_values(array_unique($json->labels));
        $json->hiddenLabels = array_values(array_unique($json->hiddenLabels));

        if (empty($json->labels)) {
            unset($json->labels);
        }
        if (empty($json->hiddenLabels)) {
            unset($json->hiddenLabels);
        }

        return $request->withParsedBody($json);
    }
}
