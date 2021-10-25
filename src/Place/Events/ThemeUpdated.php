<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Place\PlaceEvent;

/**
 * @deprecated Only here to make sure an event stream can be loaded.
 * Places should never have a theme but this was incorrectly provided as a possibility in the past.
 */
final class ThemeUpdated extends PlaceEvent
{
    public static function deserialize(array $data): ThemeUpdated
    {
        return new self($data['item_id']);
    }
}
