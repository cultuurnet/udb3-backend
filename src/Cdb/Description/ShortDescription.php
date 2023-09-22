<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

final class ShortDescription
{
    use IsString;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public static function fromCdbXmlToJsonLdFormat(string $shortDescriptionAsString): ShortDescription
    {
        return new ShortDescription(
            (new CdbXmlShortDescriptionToJsonLdFilter())->filter($shortDescriptionAsString)
        );
    }
}
