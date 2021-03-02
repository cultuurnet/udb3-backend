<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Identity\RegexUUIDParser;

class OrganizerIDParser extends RegexUUIDParser
{
    // @codingStandardsIgnoreStart
    public const REGEX = '/\\/organizer[s]?\\/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12})[\\/]?/';
    // @codingStandardsIgnoreEnd

    public function __construct()
    {
        parent::__construct(self::REGEX, 'OrganizerID');
    }
}
