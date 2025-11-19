<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class MockStripTags
{
    use IsString;
    use StripTags;

    public function __construct(string $value)
    {
        $value = $this->stripTags($value);
        $this->setValue($value);
    }
}
