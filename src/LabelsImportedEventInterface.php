<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

interface LabelsImportedEventInterface
{
    /**
     * @return string
     */
    public function getItemId();

    /**
     * @return string[]
     */
    public function getAllLabelNames(): array;

    /**
     * @return string[]
     */
    public function getVisibleLabelNames(): array;

    /**
     * @return string[]
     */
    public function getHiddenLabelNames(): array;
}
