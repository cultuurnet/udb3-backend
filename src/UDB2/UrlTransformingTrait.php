<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2;

use ValueObjects\Web\Url;

trait UrlTransformingTrait
{
    /**
     * @var UrlTransformerInterface|null
     */
    protected $urlTransformer;

    /**
     * @return $this
     */
    public function withUrlTransformer(UrlTransformerInterface $transformer)
    {
        $this->urlTransformer = $transformer;
        return $this;
    }

    /**
     * @return Url
     */
    public function transformUrl(Url $url)
    {
        return $this->urlTransformer ? $this->urlTransformer->transform($url) : $url;
    }
}
