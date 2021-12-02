<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;

final class DBALNewsArticleRepository implements NewsArticleRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getById(UUID $id): NewsArticle
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $newsArticleRows = $queryBuilder
            ->select('*')
            ->from('news_article')
            ->where('id = :id')
            ->setParameter(':id', $id->toString())
            ->execute()
            ->fetchAll(FetchMode::ASSOCIATIVE);

        if (count($newsArticleRows) !== 1) {
            throw new NewsArticleNotFoundException($id);
        }

        return $this->createNewsArticle($newsArticleRows[0]);
    }

    public function getAll(): NewsArticles
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $newsArticleRows = $queryBuilder
            ->select('*')
            ->from('news_article')
            ->execute()
            ->fetchAll(FetchMode::ASSOCIATIVE);

        return $this->createNewsArticles($newsArticleRows);
    }

    private function createNewsArticle(array $newsArticleRow): NewsArticle
    {
        return new NewsArticle(
            new UUID($newsArticleRow['id']),
            $newsArticleRow['headline'],
            new Language($newsArticleRow['in_language']),
            $newsArticleRow['text'],
            $newsArticleRow['about'],
            $newsArticleRow['publisher'],
            new Url($newsArticleRow['url']),
            new Url($newsArticleRow['publisher_logo']),
        );
    }

    private function createNewsArticles(array $newsArticleRows): NewsArticles
    {
        return new NewsArticles(
            ...array_map(
                fn (array $newsArticleRow) => $this->createNewsArticle($newsArticleRow),
                $newsArticleRows
            )
        );
    }
}
