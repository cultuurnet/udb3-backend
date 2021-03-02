<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Translation;

use CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour\FiltersDuplicates;
use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class Languages extends Collection
{
    use FiltersDuplicates;

    /**
     * @param Language[] ...$languages
     */
    public function __construct(Language ...$languages)
    {
        $filtered = $this->filterDuplicateValues($languages);
        parent::__construct(...$filtered);
    }
}
