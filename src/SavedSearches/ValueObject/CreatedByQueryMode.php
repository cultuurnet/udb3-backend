<?php

namespace CultuurNet\UDB3\SavedSearches\ValueObject;

use ValueObjects\Enum\Enum;

/**
 * @method static CreatedByQueryMode UUID()
 * @method static CreatedByQueryMode EMAIL()
 * @method static CreatedByQueryMode MIXED()
 */
class CreatedByQueryMode extends Enum
{
    const UUID = 'uuid';
    const EMAIL = 'email';
    const MIXED = 'mixed';
}
