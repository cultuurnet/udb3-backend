<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class LabelEditor
{
    private const PROPERTY_LABEL = 'dcat:keyword';

    public function setLabels(Resource $resource, Labels $getLabels): void
    {
        /** @var Label $label */
        foreach ($getLabels as $label) {
            $labelType = $label->isVisible() ? 'labeltype:publiek' : 'labeltype:verborgen';

            $resource->addLiteral(
                self::PROPERTY_LABEL,
                new Literal($label->getName()->toString(), null, $labelType)
            );
        }
    }
}
