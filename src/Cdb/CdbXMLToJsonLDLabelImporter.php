<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use stdClass;

final class CdbXMLToJsonLDLabelImporter
{
    private ReadRepositoryInterface $labelReadRepository;

    public function __construct(ReadRepositoryInterface $labelReadRepository)
    {
        $this->labelReadRepository = $labelReadRepository;
    }

    public function importLabels(CultureFeed_Cdb_Item_Base $item, stdClass $jsonLD): void
    {
        $keywords = array_values($item->getKeywords(true));
        $labels = LabelsFactory::createLabelsFromKeywords(...$keywords);
        $labelsWithCorrectVisibility = new Labels();

        /** @var Label $label */
        foreach ($labels as $label) {
            $labelReadModel = $this->labelReadRepository->getByName($label->getName()->toString());
            if ($labelReadModel) {
                $isVisible = $labelReadModel->getVisibility()->sameAs(Visibility::visible());
                $label = new Label($label->getName(), $isVisible);
            }
            $labelsWithCorrectVisibility = $labelsWithCorrectVisibility->with($label);
        }

        $visibleLabels = $labelsWithCorrectVisibility->getVisibleLabels()->toArrayOfStringNames();
        $hiddenLabels = $labelsWithCorrectVisibility->getHiddenLabels()->toArrayOfStringNames();

        unset($jsonLD->labels, $jsonLD->hiddenLabels);

        if (!empty($visibleLabels)) {
            $jsonLD->labels = $visibleLabels;
        }
        if (!empty($hiddenLabels)) {
            $jsonLD->hiddenLabels = $hiddenLabels;
        }
    }
}
