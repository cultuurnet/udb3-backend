<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use InvalidArgumentException;

/**
 * @todo Check format using a regex?
 */
class CategoryID
{
    use IsString;
    use IsNotEmpty;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        try {
            $this->guardNotEmpty($value);
        } catch (InvalidArgumentException $exception) {
            throw new EmptyCategoryId('Category cannot be empty.');
        }

        $this->setValue($value);
    }
}
