<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\StringLiteral;

final class ImportLabels extends AbstractCommand
{
    private Labels $labels;

    public function __construct(string $itemId, Labels $labels)
    {
        parent::__construct($itemId);
        $this->labels = $labels;
    }

    public function getLabels(): Labels
    {
        return $this->labels;
    }

    public function getLabelNames(): array
    {
        return array_map(
            function (Label $label) {
                return new StringLiteral($label->getName()->toString());
            },
            $this->getLabels()->toArray()
        );
    }
}
