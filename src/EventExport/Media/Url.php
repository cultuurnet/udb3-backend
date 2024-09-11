<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Media;

use stdClass;
use Webmozart\Assert\Assert;

class Url implements MediaSpecificationInterface
{
    private string $url;

    public function __construct(string $url)
    {
        Assert::stringNotEmpty($url);
        $this->url = $url;
    }

    public function matches(stdClass $mediaObject): bool
    {
        Assert::object($mediaObject);
        return $mediaObject->contentUrl === $this->url;
    }
}
