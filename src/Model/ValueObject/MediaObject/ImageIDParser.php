<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\RegexUuidParser;

class ImageIDParser extends RegexUuidParser
{
    // @codingStandardsIgnoreStart
    public const REGEX = '/\\/(media|image[s]?)\\/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12})/';
    // @codingStandardsIgnoreEnd

    public function __construct()
    {
        parent::__construct(self::REGEX, 'MediaObject ID', 2);
    }
}
