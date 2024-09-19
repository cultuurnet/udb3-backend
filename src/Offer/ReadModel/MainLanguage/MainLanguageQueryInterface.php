<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\MainLanguage;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

interface MainLanguageQueryInterface
{
    /**
     * @throws EntityNotFoundException
     */
    public function execute(string $cdbid): Language;
}
