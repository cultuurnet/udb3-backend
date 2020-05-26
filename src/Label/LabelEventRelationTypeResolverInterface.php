<?php

namespace CultuurNet\UDB3\Label;

use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\LabelsImportedEventInterface;

interface LabelEventRelationTypeResolverInterface
{
    /**
     * @param LabelEventInterface $labelEvent
     * @return RelationType
     * @throws \InvalidArgumentException
     */
    public function getRelationType(LabelEventInterface $labelEvent);

    /**
     * @param LabelsImportedEventInterface $labelsImported
     * @return RelationType
     */
    public function getRelationTypeForImport(LabelsImportedEventInterface $labelsImported);
}
