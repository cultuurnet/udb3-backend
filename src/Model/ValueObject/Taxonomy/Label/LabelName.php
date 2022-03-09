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

    public const REGEX = '/^(?=.{2,255}$)(?=.*\S.*\S.*)[^;]*$/';

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $value = $this->trim($value);

        $this->guardRegexPattern(self::REGEX, $value);

        $this->setValue($value);
    }
}
