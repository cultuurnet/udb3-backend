<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\ValueObject;

use ValueObjects\Enum\Enum;

/**
 * @method static CreatedByQueryMode UUID()
 * @method static CreatedByQueryMode EMAIL()
 * @method static CreatedByQueryMode MIXED()
 */
class CreatedByQueryMode extends Enum
{
    public const UUID = 'uuid';
    public const EMAIL = 'email';
    public const MIXED = 'mixed';
}
