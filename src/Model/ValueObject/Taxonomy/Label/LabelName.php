<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MatchesRegexPattern;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;

final class LabelName
{
    use IsString;
    use Trims;
    use MatchesRegexPattern;

    public const REGEX = '/^(?=.{2,255}$)(?=.*\S.*\S.*)[^;]*$/';

    public const REGEX_SUGGESTIONS = '/^(?![_ \-])(?!.*[_ \-]$)[a-zA-ZÀ-ÿ\d_ \-]{2,50}$/';

    public function __construct(string $value)
    {
        $value = $this->trim($value);

        $this->guardRegexPattern(self::REGEX, $value);

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
