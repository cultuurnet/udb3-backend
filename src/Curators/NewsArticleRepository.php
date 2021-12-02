<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

interface NewsArticleRepository
{
    public function getById(UUID $id): NewsArticle;

    public function getAll(): NewsArticles;
}
