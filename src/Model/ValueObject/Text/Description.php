<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Text;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Sanitizes;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;
use InvalidArgumentException;

class Description
{
    use IsString;
    use IsNotEmpty;
    use Trims;
    use Sanitizes;

    public function __construct(string $value)
    {
        $value = $this->trim($value);
        $value = $this->sanitize($value);
        try {
            $this->guardNotEmpty($value);
        } catch (InvalidArgumentException $e) {
            throw new DescriptionShouldNotBeEmpty();
        }
        $this->setValue($value);
    }
}
