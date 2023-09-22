<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

final class LongDescription
{
    use IsString;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public static function fromCdbXmlToJsonLdFormat(string $longDescriptionAsString): self
    {
        return new self(
            (new CdbXmlLongDescriptionToJsonLdFilter())->filter($longDescriptionAsString)
        );
    }
}
