<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Translation;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MatchesRegexPattern;

class Language
{
    use IsString;
    use MatchesRegexPattern;

    public const REGEX = '/^[a-z]{2}$/';

    public function __construct(string $code)
    {
        $this->guardRegexPattern(self::REGEX, $code);
        $this->setValue($code);
    }

    public function getCode(): string
    {
        return $this->toString();
    }
}
