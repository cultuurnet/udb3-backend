<?php

namespace CultuurNet\UDB3\Model\Event;

use CultuurNet\UDB3\Model\ValueObject\Identity\RegexUUIDParser;

class EventIDParser extends RegexUUIDParser
{
    // @codingStandardsIgnoreStart
    const REGEX = '/\\/event[s]?\\/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12})[\\/]?/';
    // @codingStandardsIgnoreEnd

    public function __construct()
    {
        parent::__construct(self::REGEX, 'EventID');
    }
}
