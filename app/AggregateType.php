<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use ValueObjects\Enum\Enum;

/**
 * @method static AggregateType EVENT()
 * @method static AggregateType PLACE()
 * @method static AggregateType VARIATION()
 * @method static AggregateType ORGANIZER()
 * @method static AggregateType MEDIA_OBJECT()
 * @method static AggregateType ROLE()
 * @method static AggregateType LABEL()
 */
class AggregateType extends Enum
{
    public const EVENT = 'event';
    public const PLACE = 'place';
    public const VARIATION = 'variation';
    public const ORGANIZER = 'organizer';
    public const MEDIA_OBJECT = 'media_object';
    public const ROLE = 'role';
    public const LABEL = 'label';
}
