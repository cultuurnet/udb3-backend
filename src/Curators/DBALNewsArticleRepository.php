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
            throw new NewsArticleNotFound($id);
        }

        return $this->createNewsArticle($newsArticleRows[0]);
    }

    public function search(NewsArticleSearch $newsArticleSearch): NewsArticles
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $query = $queryBuilder
            ->select('*')
            ->from('news_article');

        if ($newsArticleSearch->getAbout()) {
            $query = $query
                ->andWhere('about = :about')
                ->setParameter(':about', $newsArticleSearch->getAbout());
        }

        if ($newsArticleSearch->getPublisher()) {
            $query = $query
                ->andWhere('publisher = :publisher')
                ->setParameter(':publisher', $newsArticleSearch->getPublisher());
        }

        if ($newsArticleSearch->getUrl()) {
            $query = $query
                ->andWhere('url = :url')
                ->setParameter(':url', $newsArticleSearch->getUrl());
        }

        if ($newsArticleSearch->getStartPage() > 1) {
            $query = $query->setFirstResult(
                ($newsArticleSearch->getStartPage() - 1) * $newsArticleSearch->getItemsPerPage()
            );
        }

        $newsArticleRows = $query
            ->setMaxResults($newsArticleSearch->getItemsPerPage())
            ->execute()
            ->fetchAll(FetchMode::ASSOCIATIVE);

        return $this->createNewsArticles($newsArticleRows);
    }

    public function create(NewsArticle $newsArticle): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->insert('news_article')
            ->values(
                [
                    'id' => ':id',
                    'headline' => ':headline',
                    'in_language' => ':in_language',
                    'text' => ':text',
                    'about' => ':about',
                    'publisher' => ':publisher',
                    'url' => ':url',
                    'publisher_logo' => ':publisher_logo',
                ]
            )
            ->setParameters(
                [
                    ':id' => $newsArticle->getId()->toString(),
                    'headline' => $newsArticle->getHeadline(),
                    'in_language' => $newsArticle->getLanguage()->toString(),
                    'text' => $newsArticle->getText(),
                    'about' => $newsArticle->getAbout(),
                    'publisher' => $newsArticle->getPublisher(),
                    'url' => $newsArticle->getUrl()->toString(),
                    'publisher_logo' => $newsArticle->getPublisherLogo()->toString(),
                ]
            )
            ->execute();
    }

    public function update(NewsArticle $newsArticle): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->update('news_article')
            ->where('id = :id')
            ->set('headline', ':headline')
            ->set('in_language', ':in_language')
            ->set('text', ':text')
            ->set('about', ':about')
            ->set('publisher', ':publisher')
            ->set('url', ':url')
            ->set('publisher_logo', ':publisher_logo')
            ->setParameters(
                [
                    ':id' => $newsArticle->getId()->toString(),
                    'headline' => $newsArticle->getHeadline(),
                    'in_language' => $newsArticle->getLanguage()->toString(),
                    'text' => $newsArticle->getText(),
                    'about' => $newsArticle->getAbout(),
                    'publisher' => $newsArticle->getPublisher(),
                    'url' => $newsArticle->getUrl()->toString(),
                    'publisher_logo' => $newsArticle->getPublisherLogo()->toString(),
                ]
            )
            ->execute();
    }

    public function delete(UUID $id): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->delete('news_article')
            ->where('id = :id')
            ->setParameter(':id', $id->toString())
            ->execute();
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
