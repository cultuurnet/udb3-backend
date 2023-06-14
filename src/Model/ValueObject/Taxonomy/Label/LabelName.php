<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MatchesRegexPattern;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;

class LabelName
{
    use IsString;
    use Trims;
    use MatchesRegexPattern;

    public const LEGACY_REGEX = '/^(?=.{2,255}$)(?=.*\S.*\S.*)[^;]*$/';

    public const REGEX = '/^(?![-_ ])(?=.{2,50}$)(?=.*\S.*\S.*)[^;,\#\'!&]*$/';

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $value = $this->trim($value);

        $this->guardRegexPattern(self::LEGACY_REGEX, $value);

        $this->setValue($value);
    }

    public function toLowerCase(): LabelName
    {
        return new self(mb_strtolower($this->toString(), 'UTF-8'));
    }

    public function sameAs(LabelName $other): bool
    {
        return $this->toLowerCase()->toString() === $other->toLowerCase()->toString();
    }
}
