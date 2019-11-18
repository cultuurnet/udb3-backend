<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Enum;

/**
 * Class EventDescription
 * @package CultuurNet\UDB3\Place\ReadModel\Enum
 *
 * @TODO: FIX BEFORE MERGE - use real names for description
 */
class EventDescription
{
    const CREATED = 'Created';
    const DELETED = 'Deleted';
    const LABEL_ADDED = 'label-added';
    const LABEL_REMOVED = 'label-removed';
    const DESCRIPTION_TRANSLATED = 'description-translated';
    const TITLE_TRANSLATED = 'title-translated';
}
