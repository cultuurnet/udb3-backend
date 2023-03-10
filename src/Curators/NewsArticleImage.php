<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class NewsArticleImage
{
    private Url $imageUrl;

    private CopyrightHolder $copyrightHolder;

    public function __construct(
        Url $imageUrl,
        CopyrightHolder $copyrightHolder
    ) {
        $this->imageUrl = $imageUrl;
        $this->copyrightHolder = $copyrightHolder;
    }

    public function getImageUrl(): Url
    {
        return $this->imageUrl;
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }
}
