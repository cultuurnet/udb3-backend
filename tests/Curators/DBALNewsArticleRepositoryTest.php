<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class DBALNewsArticleRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALNewsArticleRepository $dbalNewsArticleRepository;

    protected function setUp(): void
    {
        $this->dbalNewsArticleRepository = new DBALNewsArticleRepository($this->getConnection());

        $this->createTable(
            NewsArticleSchemaConfigurator::getTableDefinition(
                $this->createSchema()
            )
        );

        $this->getConnection()->insert(
            'news_article',
            [
                'id' => '4bd47771-4c83-4023-be0d-e4e93681c2ba',
                'headline' => 'publiq wint API award',
                'in_language' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisher_logo' => 'https://www.bill.be/img/favicon.png',
            ]
        );

        $this->getConnection()->insert(
            'news_article',
            [
                'id' => '9a94f933-0ccd-477b-8a74-87d086b04d3b',
                'headline' => 'madewithlove helps porting migrator API',
                'in_language' => 'en',
                'text' => 'In 2021 madewithlove helps publiq with migrating the curator API',
                'about' => 'c737abbb-d436-497d-a179-4c5d5581365e',
                'publisher' => 'BUZZ',
                'url' => 'https://www.madewithlove.be/blog/curator-migratie',
                'publisher_logo' => 'https://www.buzz.be/img/favicon.png',
            ]
        );
    }

    /**
     * @test
     */
    public function it_can_get_a_news_article(): void
    {
        $this->assertEquals(
            new NewsArticle(
                new UUID('4bd47771-4c83-4023-be0d-e4e93681c2ba'),
                'publiq wint API award',
                new Language('nl'),
                'Op 10 januari 2020 wint publiq de API award',
                '17284745-7bcf-461a-aad0-d3ad54880e75',
                'BILL',
                new Url('https://www.publiq.be/blog/api-reward'),
                new Url('https://www.bill.be/img/favicon.png')
            ),
            $this->dbalNewsArticleRepository->getById(new UUID('4bd47771-4c83-4023-be0d-e4e93681c2ba'))
        );
    }

    /**
     * @test
     */
    public function it_throws_when_news_article_not_found(): void
    {
        $this->expectException(NewsArticleNotFound::class);
        $this->expectExceptionMessage('News article with id "6a883273-4995-4455-9156-eb1f920253be" was not found.');

        $this->dbalNewsArticleRepository->getById(new UUID('6a883273-4995-4455-9156-eb1f920253be'));
    }

    /**
     * @test
     */
    public function it_can_get_all_news_articles(): void
    {
        $this->assertEquals(
            new NewsArticles(
                new NewsArticle(
                    new UUID('4bd47771-4c83-4023-be0d-e4e93681c2ba'),
                    'publiq wint API award',
                    new Language('nl'),
                    'Op 10 januari 2020 wint publiq de API award',
                    '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'BILL',
                    new Url('https://www.publiq.be/blog/api-reward'),
                    new Url('https://www.bill.be/img/favicon.png')
                ),
                new NewsArticle(
                    new UUID('9a94f933-0ccd-477b-8a74-87d086b04d3b'),
                    'madewithlove helps porting migrator API',
                    new Language('en'),
                    'In 2021 madewithlove helps publiq with migrating the curator API',
                    'c737abbb-d436-497d-a179-4c5d5581365e',
                    'BUZZ',
                    new Url('https://www.madewithlove.be/blog/curator-migratie'),
                    new Url('https://www.buzz.be/img/favicon.png')
                )
            ),
            $this->dbalNewsArticleRepository->getAll()
        );
    }

    /**
     * @test
     */
    public function it_returns_empty_news_articles_collection_when_no_articles_present(): void
    {
        $this->dbalNewsArticleRepository->delete(new UUID('4bd47771-4c83-4023-be0d-e4e93681c2ba'));
        $this->dbalNewsArticleRepository->delete(new UUID('9a94f933-0ccd-477b-8a74-87d086b04d3b'));

        $this->assertEquals(
            new NewsArticles(),
            $this->dbalNewsArticleRepository->getAll()
        );
    }

    /**
     * @test
     */
    public function it_can_create_a_news_article(): void
    {
        $newsArticle = new NewsArticle(
            new UUID('727cf17c-d81f-4ec6-ba39-ef0227b5eb40'),
            'Creating news articles works',
            new Language('en'),
            'This test covers the creation of news articles',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'TECH',
            new Url('https://www.tech.com/blog/create'),
            new Url('https://www.tech.com/img/favicon.png')
        );

        $this->dbalNewsArticleRepository->create($newsArticle);

        $this->assertEquals(
            $newsArticle,
            $this->dbalNewsArticleRepository->getById(new UUID('727cf17c-d81f-4ec6-ba39-ef0227b5eb40'))
        );
    }

    /**
     * @test
     */
    public function it_can_update_a_news_article(): void
    {
        $newsArticle = new NewsArticle(
            new UUID('4bd47771-4c83-4023-be0d-e4e93681c2ba'),
            'Updating news articles works',
            new Language('nl'),
            'This test covers the update of news articles',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'UPDATE',
            new Url('https://www.update.com/blog/create'),
            new Url('https://www.update.com/img/favicon.png')
        );

        $this->dbalNewsArticleRepository->update($newsArticle);

        $this->assertEquals(
            $newsArticle,
            $this->dbalNewsArticleRepository->getById(new UUID('4bd47771-4c83-4023-be0d-e4e93681c2ba'))
        );
    }

    /**
     * @test
     */
    public function it_can_delete_a_news_article(): void
    {
        $this->dbalNewsArticleRepository->getById(new UUID('4bd47771-4c83-4023-be0d-e4e93681c2ba'));

        $this->dbalNewsArticleRepository->delete(new UUID('4bd47771-4c83-4023-be0d-e4e93681c2ba'));

        $this->expectException(NewsArticleNotFound::class);

        $this->dbalNewsArticleRepository->getById(new UUID('4bd47771-4c83-4023-be0d-e4e93681c2ba'));
    }

    /**
     * @test
     */
    public function it_can_handle_an_already_deleted_news_article(): void
    {
        $this->dbalNewsArticleRepository->delete(new UUID('3a9f6da3-938c-4074-a5c9-73f254899d09'));
        $this->addToAssertionCount(1);
    }
}
