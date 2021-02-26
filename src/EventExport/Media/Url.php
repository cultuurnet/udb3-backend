<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Media;

use stdClass;
use Webmozart\Assert\Assert;

class Url implements MediaSpecificationInterface
{
    /**
     * @var string
     */
    private $url;

    /**
     * Url constructor.
     *
     * @param string $url
     */
    public function __construct($url)
    {
        Assert::stringNotEmpty($url);
        $this->url = $url;
    }

    /**
     * @param stdClass $mediaObject
     * @return bool
     */
    public function matches($mediaObject)
    {
        Assert::object($mediaObject);
        return $mediaObject->contentUrl === $this->url;
    }
}
