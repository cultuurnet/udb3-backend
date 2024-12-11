<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Place;

use CultuurNet\UDB3\Model\ValueObject\Identity\RegexUuidParser;

class PlaceIDParser extends RegexUuidParser
{
    // @codingStandardsIgnoreStart
    public const REGEX = '/\\/place[s]?\\/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12})[\\/]?/';
    // @codingStandardsIgnoreEnd

    public function __construct()
    {
        parent::__construct(self::REGEX, 'PlaceID');
    }
}
