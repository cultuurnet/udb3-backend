<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\ValueObject;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static CreatedByQueryMode uuid()
 * @method static CreatedByQueryMode email()
 * @method static CreatedByQueryMode mixed()
 */
class CreatedByQueryMode extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'uuid',
            'email',
            'mixed',
        ];
    }
}
