<?php

declare(strict_types=1);

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

    /**
     * @return array<string>
     */
    public function getAllLabelNames(): array;

    /**
     * @return array<string>
     */
    public function getVisibleLabelNames(): array;

    /**
     * @return array<string>
     */
    public function getHiddenLabelNames(): array;
}
