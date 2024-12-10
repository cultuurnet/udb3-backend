<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Label\ReadModels\Roles\LabelRolesWriteRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class LabelRolesWriteRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private string $labelRolesTableName;

    private LabelRolesWriteRepositoryInterface $labelRolesWriteRepository;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->labelRolesTableName = 'label_roles';

        $this->labelRolesWriteRepository = new LabelRolesWriteRepository(
            $this->connection,
            $this->labelRolesTableName
        );
    }

    /**
     * @test
     */
    public function it_inserts_a_label_and_related_role(): void
    {
        $labelId = new Uuid('bc579c8e-cc4a-4c21-abb2-a7d63b5f820f');
        $roleId = new Uuid('2fd60f5c-8d0f-4efd-a005-128636a5530b');

        $this->labelRolesWriteRepository->insertLabelRole($labelId, $roleId);

        $actualRows = $this->getRows();

        $expectedRows = [
            [
                ColumnNames::LABEL_ID_COLUMN => $labelId->toString(),
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
            ],
        ];

        $this->assertEquals($expectedRows, $actualRows);
    }

    /**
     * @test
     */
    public function it_removes_a_label_and_related_role(): void
    {
        $labelId1 = new Uuid('b18215d5-2d66-45e1-ae5d-1316a3b40897');
        $labelId2 = new Uuid('72bffc2b-d784-403c-97b5-ef4f74decd5b');
        $roleId1 = new Uuid('167ae73d-52ab-45c1-862e-d3638a1c7c5a');
        $roleId2 = new Uuid('35acce4d-25e6-441b-8162-87a764e77ed4');

        $this->insertLabelRole($labelId1, $roleId1);
        $this->insertLabelRole($labelId1, $roleId2);
        $this->insertLabelRole($labelId2, $roleId2);

        $this->labelRolesWriteRepository->removeLabelRole($labelId1, $roleId2);

        $expectedRows = [
            [
                ColumnNames::LABEL_ID_COLUMN => $labelId1->toString(),
                ColumnNames::ROLE_ID_COLUMN => $roleId1->toString(),
            ],
            [
                ColumnNames::LABEL_ID_COLUMN => $labelId2->toString(),
                ColumnNames::ROLE_ID_COLUMN => $roleId2->toString(),
            ],
        ];

        $actualRows = $this->getRows();

        $this->assertEqualsCanonicalizing($expectedRows, $actualRows);
    }

    /**
     * @test
     */
    public function it_removes_a_role_and_all_related_labels(): void
    {
        $labelId1 = new Uuid('d5f8236b-f252-4d62-984b-e956dc2da15f');
        $labelId2 = new Uuid('8b04e55d-08de-491c-9387-59c24197d42d');
        $roleId1 = new Uuid('9e91887a-e40a-4dc8-a8b7-1b946146d59d');
        $roleId2 = new Uuid('47ee089d-39de-4fdc-a8de-d07bea709865');

        $this->insertLabelRole($labelId1, $roleId1);
        $this->insertLabelRole($labelId1, $roleId2);
        $this->insertLabelRole($labelId2, $roleId2);

        $this->labelRolesWriteRepository->removeRole($roleId2);

        $expectedRows = [
            [
                ColumnNames::LABEL_ID_COLUMN => $labelId1->toString(),
                ColumnNames::ROLE_ID_COLUMN => $roleId1->toString(),
            ],
        ];

        $actualRows = $this->getRows();

        $this->assertEquals($expectedRows, $actualRows);
    }


    private function insertLabelRole(Uuid $labelId, Uuid $roleId): void
    {
        $this->connection->insert(
            $this->labelRolesTableName,
            [
                ColumnNames::LABEL_ID_COLUMN => $labelId->toString(),
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );
    }

    private function getRows(): array
    {
        $sql = 'SELECT * FROM ' . $this->labelRolesTableName;
        $statement = $this->connection->executeQuery($sql);

        return $statement->fetchAllAssociative();
    }
}
