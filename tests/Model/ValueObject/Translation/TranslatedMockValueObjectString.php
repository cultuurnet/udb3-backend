<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Translation;

class TranslatedMockValueObjectString extends TranslatedValueObject
{
    protected function getValueObjectClassName()
    {
        return MockValueObjectString::class;
    }
}
