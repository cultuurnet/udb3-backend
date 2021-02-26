<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Label;

use CultuurNet\UDB3\Label;

interface UiTPASLabelsRepository
{
    /**
     * @return Label[]
     *   Associative array of card system ids as keys and corresponding Label objects as values.
     */
    public function loadAll(): array;
}
