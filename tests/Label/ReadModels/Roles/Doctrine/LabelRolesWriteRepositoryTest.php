<?php

namespace CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Label\ReadModels\Roles\LabelRolesWriteRepositoryInterface;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class LabelRolesWriteRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var StringLiteral
     */
    private $labelRolesTableName;

    /**
     * @var LabelRolesWriteRepositoryInterface
     */
    private $labelRolesWriteRepository;

    protected function setUp()
    {
        $this->labelRolesTableName = new StringLiteral('label_roles');

        $schemaConfigurator = new SchemaConfigurator(
            $this->labelRolesTableName
        );
        $schemaConfigurator->configure(
            $this->getConnection()->getSchemaManager()
        );

        $this->labelRolesWriteRepository = new LabelRolesWriteRepository(
            $this->connection,
            $this->labelRolesTableName
        );
    }

    /**
     * @test
     */
    public function it_inserts_a_label_and_related_role()
    {
        $labelId = new UUID();
        $roleId = new UUID();

        $this->labelRolesWriteRepository->insertLabelRole($labelId, $roleId);

        $actualRows = $this->getRows();

        $expectedRows = [
            [
                SchemaConfigurator::LABEL_ID_COLUMN => $labelId->toNative(),
                SchemaConfigurator::ROLE_ID_COLUMN => $roleId->toNative(),
            ],
        ];

        $this->assertEquals($expectedRows, $actualRows);
    }

    /**
     * @test
     */
    public function it_removes_a_label_and_related_role()
    {
        $labelId1 = new UUID();
        $labelId2 = new UUID();
        $roleId1 = new UUID();
        $roleId2 = new UUID();

        $this->insertLabelRole($labelId1, $roleId1);
        $this->insertLabelRole($labelId1, $roleId2);
        $this->insertLabelRole($labelId2, $roleId2);

        $this->labelRolesWriteRepository->removeLabelRole($labelId1, $roleId2);

        $expectedRows = [
            [
                SchemaConfigurator::LABEL_ID_COLUMN => $labelId1->toNative(),
                SchemaConfigurator::ROLE_ID_COLUMN => $roleId1->toNative(),
            ],
            [
                SchemaConfigurator::LABEL_ID_COLUMN => $labelId2->toNative(),
                SchemaConfigurator::ROLE_ID_COLUMN => $roleId2->toNative(),
            ],
        ];

        $actualRows = $this->getRows();

        $this->assertEquals($expectedRows, $actualRows);
    }

    /**
     * @test
     */
    public function it_removes_a_role_and_all_related_labels()
    {
        $labelId1 = new UUID();
        $labelId2 = new UUID();
        $roleId1 = new UUID();
        $roleId2 = new UUID();

        $this->insertLabelRole($labelId1, $roleId1);
        $this->insertLabelRole($labelId1, $roleId2);
        $this->insertLabelRole($labelId2, $roleId2);

        $this->labelRolesWriteRepository->removeRole($roleId2);

        $expectedRows = [
            [
                SchemaConfigurator::LABEL_ID_COLUMN => $labelId1->toNative(),
                SchemaConfigurator::ROLE_ID_COLUMN => $roleId1->toNative(),
            ],
        ];

        $actualRows = $this->getRows();

        $this->assertEquals($expectedRows, $actualRows);
    }

    /**
     * @param UUID $labelId
     * @param UUID $roleId
     */
    private function insertLabelRole(UUID $labelId, UUID $roleId)
    {
        $this->connection->insert(
            $this->labelRolesTableName,
            [
                SchemaConfigurator::LABEL_ID_COLUMN => $labelId->toNative(),
                SchemaConfigurator::ROLE_ID_COLUMN => $roleId->toNative(),
            ]
        );
    }

    /**
     * @return array
     */
    private function getRows()
    {
        $sql = 'SELECT * FROM ' . $this->labelRolesTableName->toNative();
        $statement = $this->connection->executeQuery($sql);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
