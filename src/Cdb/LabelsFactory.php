<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use CultureFeed_Cdb_Data_Keyword;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use InvalidArgumentException;

final class LabelsFactory
{
    public static function createLabelsFromKeywords(CultureFeed_Cdb_Data_Keyword ...$keywords): Labels
    {
        $labels = [];
        foreach ($keywords as $keyword) {
            try {
                $labelName = new LabelName($keyword->getValue());
            } catch (InvalidArgumentException $exception) {
                // Skip keywords that are not valid label names. (Not much possible to fix that.)
                continue;
            }
            $labels[] = new Label($labelName, $keyword->isVisible());
        }
        return new Labels(...$labels);
    }
}
