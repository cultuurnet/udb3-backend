<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\LabelsImportedEventInterface;

interface LabelEventRelationTypeResolverInterface
{
    /**
     * @return RelationType
     * @throws \InvalidArgumentException
     */
    public function getRelationType(LabelEventInterface $labelEvent);

    /**
     * @return RelationType
     */
    public function getRelationTypeForImport(LabelsImportedEventInterface $labelsImported);
}
