<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

/**
 * @todo Check against predefined list of possible domains?
 */
class CategoryDomain
{
    use IsString;
    use IsNotEmpty;

    public function __construct(string $value)
    {
        $this->guardNotEmpty($value);
        $this->setValue($value);
    }

    public static function eventType(): CategoryDomain
    {
        return new CategoryDomain('eventtype');
    }
}
