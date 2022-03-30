<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

interface ExcludedLabelsRepository
{
    /**
     * @return string[]
     */
    public function getAll(): array;
}
