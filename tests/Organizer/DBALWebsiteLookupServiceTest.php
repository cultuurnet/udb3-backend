<?php

namespace CultuurNet\UDB3\Organizer;

use Broadway\EventStore\DBALEventStore;
use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintServiceInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueDBALEventStoreDecorator;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class DBALWebsiteLookupServiceTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var DBALWebsiteLookupService
     */
    private $lookupService;

    /**
     * @var string
     */
    private $tableName;

    public function setUp()
    {
        /* @var DBALEventStore $dbalEventStore */
        $dbalEventStore = $this->createMock(DBALEventStore::class);

        $this->tableName = 'mock_organizer_unique_websites';

        /* @var UniqueConstraintServiceInterface $uniqueConstraintService */
        $uniqueConstraintService = $this->createMock(UniqueConstraintServiceInterface::class);

        $uniqueDBALEventStoreDecorator = new UniqueDBALEventStoreDecorator(
            $dbalEventStore,
            $this->getConnection(),
            new StringLiteral($this->tableName),
            $uniqueConstraintService
        );

        $schemaManager = $this->getConnection()->getSchemaManager();
        $schema = $schemaManager->createSchema();

        $table = $uniqueDBALEventStoreDecorator->configureSchema($schema);
        $schemaManager->createTable($table);

        $this->lookupService = new DBALWebsiteLookupService(
            $this->getConnection(),
            $this->tableName
        );
    }

    /**
     * @test
     */
    public function it_should_return_the_organizer_uuid_the_url_belongs_to()
    {
        $publiqId = '7736697b-da7d-4d71-b468-2bd356eb6c36';
        $publiqUrl = Url::fromNative('https://www.publiq.be');

        $uitDatabankId = '984bed58-7925-417b-ab3c-2da0e9c266b0';
        $uitDatabankUrl = Url::fromNative('https://www.uitdatabank.be');

        $this->insertOrganizerWebsite($publiqId, $publiqUrl);
        $this->insertOrganizerWebsite($uitDatabankId, $uitDatabankUrl);

        $this->assertEquals($publiqId, $this->lookupService->lookup($publiqUrl));
        $this->assertEquals($uitDatabankId, $this->lookupService->lookup($uitDatabankUrl));
        $this->assertNull($this->lookupService->lookup(Url::fromNative('https://google.com')));
    }

    /**
     * @test
     */
    public function it_should_return_null_if_the_url_does_not_belong_to_any_existing_organizer()
    {
        $url = Url::fromNative('https://publiq.be');
        $this->assertNull($this->lookupService->lookup($url));
    }

    /**
     * @param string $uuid
     * @param Url $url
     */
    private function insertOrganizerWebsite($uuid, Url $url)
    {
        $this->getConnection()->insert(
            $this->tableName,
            [
                UniqueDBALEventStoreDecorator::UUID_COLUMN => $uuid,
                UniqueDBALEventStoreDecorator::UNIQUE_COLUMN => (string) $url,
            ]
        );
    }
}
