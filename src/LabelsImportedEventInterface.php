<?php

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;

interface LabelsImportedEventInterface
{
    /**
     * @return string
     */
    public function getItemId();

    /**
     * @return Labels
     */
    public function getLabels();
}
