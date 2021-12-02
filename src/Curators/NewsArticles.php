<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class NewsArticles extends Collection
{
    public function __construct(NewsArticle ...$newsArticle)
    {
        parent::__construct(...$newsArticle);
    }
}
