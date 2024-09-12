<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Term;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use PHPUnit\Framework\TestCase;

final class TermRepositoryTest extends TestCase
{
    private const MAPPING = [
        '0.41.0.0.0' => [
            'name' => [
                'nl' => 'Thema of pretpark',
                'fr' => 'Parc à thème ou parc d\'attractions',
                'de' => 'Unterhaltungspark',
                'en' => 'Theme park',
            ],
        ],
        '0.59.0.0.0' => [
            'name' => [
                'nl' => 'Sportactiviteit',
                'fr' => 'Activité sportive',
                'de' => 'Geben Wettbewerb',
                'en' => 'Sports activity',
            ],
        ],
        '3CuHvenJ+EGkcvhXLg9Ykg' => [
            'name' => [
                'nl' => 'Archeologische Site',
            ],
        ],
        'rJRFUqmd6EiqTD4c7HS90w' => [
            'name' => [
                'nl' => 'School of onderwijscentrum',
            ],
        ],
        'no_name' => [],
        'no_name_nl' => [
            'name' => [
                'fr' => '...',
            ],
        ],
    ];

    private TermRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new TermRepository(self::MAPPING);
    }

    /**
     * @test
     */
    public function it_should_return_an_existing_term_with_label_by_id(): void
    {
        $givenId = '0.41.0.0.0';
        $expectedTerm = new Category(
            new CategoryID('0.41.0.0.0'),
            new CategoryLabel('Thema of pretpark')
        );
        $actualTerm = $this->repository->getById($givenId);
        $this->assertEquals($expectedTerm, $actualTerm);
    }

    /**
     * @test
     */
    public function it_should_return_an_existing_term_without_label(): void
    {
        $givenId = 'no_name';
        $expectedTerm = new Category(
            new CategoryID('no_name')
        );
        $actualTerm = $this->repository->getById($givenId);
        $this->assertEquals($expectedTerm, $actualTerm);
    }

    /**
     * @test
     */
    public function it_should_return_an_existing_term_without_dutch_label(): void
    {
        $givenId = 'no_name_nl';
        $expectedTerm = new Category(
            new CategoryID('no_name_nl')
        );
        $actualTerm = $this->repository->getById($givenId);
        $this->assertEquals($expectedTerm, $actualTerm);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_for_an_id_that_does_not_exist(): void
    {
        $givenId = 'does_not_exist';
        $this->expectException(TermNotFoundException::class);
        $this->repository->getById($givenId);
    }
}
