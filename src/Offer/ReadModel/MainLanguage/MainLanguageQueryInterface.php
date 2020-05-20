<?php

namespace CultuurNet\UDB3\Offer\ReadModel\MainLanguage;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Language;

interface MainLanguageQueryInterface
{
    /**
     * @param string $cdbid
     * @return Language
     * @throws EntityNotFoundException
     */
    public function execute($cdbid);
}
