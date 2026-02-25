<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Faq;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\HasMaxLength;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;

final class Question
{
    use IsString;
    use IsNotEmpty;
    use Trims;
    use HasMaxLength;

    private const MAX_LENGTH = 255;

    public function __construct(string $value)
    {
        $value = $this->trim($value);
        $this->guardNotEmpty($value);
        $this->guardTooLong('question', $value, self::MAX_LENGTH);
        $this->setValue($value);
    }
}
