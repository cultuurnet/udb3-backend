<?php
/**
 * @file
 */

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
    const EVENT = 'event';
    const PLACE = 'place';
    const VARIATION = 'variation';
    const ORGANIZER = 'organizer';
    const MEDIA_OBJECT = 'media_object';
    const ROLE = 'role';
    const LABEL = 'label';
}