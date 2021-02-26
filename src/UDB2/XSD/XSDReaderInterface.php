<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\XSD;

interface XSDReaderInterface
{
    /**
     * @return XSD
     */
    public function read();
}
