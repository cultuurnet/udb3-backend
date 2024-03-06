<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\SavedSearches\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UDB3SavedSearchRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private string $tableName;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    private UDB3SavedSearchRepository $udb3SavedSearchRepository;

    protected function setUp(): void
    {
        $this->createTable();

        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);

        $this->udb3SavedSearchRepository = new UDB3SavedSearchRepository(
            $this->getConnection(),
            $this->tableName,
            $this->uuidGenerator,
            '6f072ba8-c510-40ac-b387-51f582650e26'
        );
    }

    /**
     * @test
     */
    public function it_can_save_a_query_with_name_for_a_user(): void
    {
        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('73bf2160-058c-4e4e-bbee-6bcbe9298596');

        $this->udb3SavedSearchRepository->write(
            '96fd6c13-eaab-4dd1-bb6a-1c483d5e40cc',
            'In Leuven',
            new QueryString('q=city:leuven')
        );

        $savedSearches = $this->getSavedSearches();

        $this->assertEquals(
            [
                new SavedSearch(
                    'In Leuven',
                    new QueryString('q=city:leuven'),
                    '73bf2160-058c-4e4e-bbee-6bcbe9298596'
                ),
            ],
            $savedSearches
        );
    }

    /**
     * @test
     */
    public function it_can_update_a_query_with_name_for_a_user(): void
    {
        $this->udb3SavedSearchRepository->update(
            '1c483d5e40cc-4dd1-4dd1-eaab-96fd6c13',
            '96fd6c13-eaab-4dd1-bb6a-1c483d5e40cc',
            'In Leuven',
            new QueryString('q=city:leuven')
        );

        $savedSearches = $this->getSavedSearches();

        $this->assertEquals(
            [
                new SavedSearch(
                    'In Leuven',
                    new QueryString('q=city:leuven'),
                    '1c483d5e40cc-4dd1-4dd1-eaab-96fd6c13'
                ),
            ],
            $savedSearches
        );
    }

    /**
     * @test
     */
    public function it_can_delete_a_query_for_a_user(): void
    {
        $this->seedSavedSearches();

        $this->udb3SavedSearchRepository->delete(
            '6f072ba8-c510-40ac-b387-51f582650e26',
            'db4c4690-84fb-4ed9-9a64-fccdd6e29f53'
        );

        $savedSearches = $this->getSavedSearches();

        $this->assertEquals(
            [
                new SavedSearch(
                    'In Leuven',
                    new QueryString('q=city:leuven'),
                    '73bf2160-058c-4e4e-bbee-6bcbe9298596'
                ),
                new SavedSearch(
                    'Alles in Tienen',
                    new QueryString('q=city:Tienen'),
                    '4de79378-d9a9-47ec-9b38-6f76f9d6df37'
                ),
            ],
            $savedSearches
        );
    }

    /**
     * @test
     */
    public function it_can_get_all_saved_searches_for_a_user(): void
    {
        $this->seedSavedSearches();

        $savedSearches = $this->udb3SavedSearchRepository->ownedByCurrentUser();

        $this->assertEquals(
            [
                new SavedSearch(
                    'Permanent in Rotselaar',
                    new QueryString('q=city:Rotselaar AND permanent:TRUE'),
                    'db4c4690-84fb-4ed9-9a64-fccdd6e29f53'
                ),
                new SavedSearch(
                    'Alles in Tienen',
                    new QueryString('q=city:Tienen'),
                    '4de79378-d9a9-47ec-9b38-6f76f9d6df37'
                ),
            ],
            $savedSearches
        );
    }

    private function createTable(): void
    {
        $this->tableName = 'saved_searches';
        $schemaConfigurator = new SchemaConfigurator($this->tableName);

        $schemaConfigurator->configure(
            $this->getConnection()->getSchemaManager()
        );
    }

    /**
     * @return SavedSearch[]
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getSavedSearches(): array
    {
        $statement = $this->connection->executeQuery(
            'SELECT * FROM ' . $this->tableName
        );
        $rows = $statement->fetchAllAssociative();

        $savedSearches = [];
        foreach ($rows as $row) {
            $savedSearches[] = new SavedSearch(
                $row[SchemaConfigurator::NAME],
                new QueryString($row[SchemaConfigurator::QUERY]),
                $row[SchemaConfigurator::ID]
            );
        }

        return $savedSearches;
    }

    private function seedSavedSearches(): void
    {
        $this->uuidGenerator->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(
                '73bf2160-058c-4e4e-bbee-6bcbe9298596',
                'db4c4690-84fb-4ed9-9a64-fccdd6e29f53',
                '4de79378-d9a9-47ec-9b38-6f76f9d6df37'
            );

        $this->udb3SavedSearchRepository->write(
            '96fd6c13-eaab-4dd1-bb6a-1c483d5e40cc',
            'In Leuven',
            new QueryString('q=city:leuven')
        );

        $this->udb3SavedSearchRepository->write(
            '6f072ba8-c510-40ac-b387-51f582650e26',
            'Permanent in Rotselaar',
            new QueryString('q=city:Rotselaar AND permanent:TRUE')
        );

        $this->udb3SavedSearchRepository->write(
            '6f072ba8-c510-40ac-b387-51f582650e26',
            'Alles in Tienen',
            new QueryString('q=city:Tienen')
        );
    }
}
