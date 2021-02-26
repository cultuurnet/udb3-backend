<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2;

use ValueObjects\Web\Url;

class OfferToSapiUrlTransformer implements UrlTransformerInterface
{
    /**
     * @var string
     *  A url format with a single $s placeholder.
     */
    private $urlFormat;

    /**
     * OfferToSapiUrlTransformer constructor.
     * @param string $urlFormat
     */
    public function __construct($urlFormat)
    {
        $this->urlFormat = $urlFormat;
    }

    /**
     * @inheritdoc
     */
    public function transform(Url $url)
    {
        $lastSlashPosition = strrpos($url, '/') + 1;
        $cdbid = substr($url, $lastSlashPosition, strlen($url) - $lastSlashPosition);

        return  Url::fromNative(sprintf($this->urlFormat, $cdbid));
    }
}
