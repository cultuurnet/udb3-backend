<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;

final class ReplaceLabels extends AbstractCommand
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
}
