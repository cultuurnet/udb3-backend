<?php

namespace CultuurNet\UDB3\UDB2;

use ValueObjects\Web\Url;

trait UrlTransformingTrait
{
    /**
     * @var UrlTransformerInterface
     */
    protected $urlTransformer;

    /**
     * @param UrlTransformerInterface $transformer
     * @return $this
     */
    public function withUrlTransformer(UrlTransformerInterface $transformer)
    {
        $this->urlTransformer = $transformer;
        return $this;
    }

    /**
     * @param Url $url
     * @return Url
     */
    public function transformUrl(Url $url)
    {
        return $this->urlTransformer ? $this->urlTransformer->transform($url) : $url;
    }
}
