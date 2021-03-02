<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class MockDigitsRegexPattern
{
    use IsString;
    use MatchesRegexPattern;

    public function __construct($value)
    {
        $this->guardRegexPattern('/\\A\\d+\\Z/', $value, 'The given value is not a digit.');
        $this->setValue($value);
    }
}
