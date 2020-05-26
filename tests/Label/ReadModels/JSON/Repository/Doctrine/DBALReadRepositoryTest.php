<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine\SchemaConfigurator as LabelRolesSchemaConfigurator;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\SchemaConfigurator as PermissionsSchemaConfigurator;
use ValueObjects\Identity\UUID;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class DBALReadRepositoryTest extends BaseDBALRepositoryTest
{
    /**
     * @var DBALReadRepository
     */
    private $dbalReadRepository;

    /**
     * @var Entity
     */
    private $entityByUuid;

    /**
     * @var Entity
     */
    private $entityByName;

    /**
     * @var Entity
     */
    private $entityPrivateAccess;

    /**
     * @var Entity
     */
    private $entityPrivateNoAccess;

    /**
     * @var StringLiteral
     */
    private $labelRolesTableName;

    /**
     * @var StringLiteral
     */
    private $userRolesTableName;

    protected function setUp()
    {
        parent::setUp();

        $this->labelRolesTableName = new StringLiteral('label_roles');
        $schemaConfigurator = new LabelRolesSchemaConfigurator(
            $this->labelRolesTableName
        );
        $schemaConfigurator->configure(
            $this->getConnection()->getSchemaManager()
        );

        $this->userRolesTableName = new StringLiteral('user_roles');
        $schemaConfigurator = new PermissionsSchemaConfigurator(
            $this->userRolesTableName,
            new StringLiteral('role_permissions')
        );
        $schemaConfigurator->configure(
            $this->getConnection()->getSchemaManager()
        );

        $this->dbalReadRepository = new DBALReadRepository(
            $this->getConnection(),
            $this->getTableName(),
            $this->labelRolesTableName,
            $this->userRolesTableName
        );

        $this->entityByUuid = new Entity(
            new UUID(),
            new StringLiteral('bibliotheekweek'),
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PUBLIC(),
            new UUID()
        );
        $this->saveEntity($this->entityByUuid);

        $this->entityByName = new Entity(
            new UUID(),
            new StringLiteral('boswandeling'),
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PUBLIC(),
            new UUID()
        );
        $this->saveEntity($this->entityByName);

        $this->entityPrivateAccess = new Entity(
            new UUID(),
            new StringLiteral('wandeltocht'),
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PRIVATE(),
            new UUID()
        );
        $this->saveEntity($this->entityPrivateAccess);

        $this->entityPrivateNoAccess = new Entity(
            new UUID(),
            new StringLiteral('stadswandeling'),
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PRIVATE(),
            new UUID()
        );
        $this->saveEntity($this->entityPrivateNoAccess);

        for ($i = 0; $i < 10; $i++) {
            $entity = new Entity(
                new UUID(),
                new StringLiteral('label' . $i),
                Visibility::VISIBLE(),
                Privacy::PRIVACY_PUBLIC(),
                new UUID()
            );
            $this->saveEntity($entity);
        }
    }

    /**
     * @test
     */
    public function it_can_get_by_uuid()
    {
        $entity = $this->dbalReadRepository->getByUuid(
            $this->entityByUuid->getUuid()
        );

        $this->assertEquals($this->entityByUuid, $entity);
    }

    /**
     * @test
     */
    public function it_returns_null_when_not_found_by_uuid()
    {
        $entity = $this->dbalReadRepository->getByUuid(
            new UUID()
        );

        $this->assertNull($entity);
    }

    /**
     * @test
     */
    public function it_can_get_by_name()
    {
        $entity = $this->dbalReadRepository->getByName(
            $this->entityByName->getName()
        );

        $this->assertEquals($this->entityByName, $entity);
    }

    /**
     * @test
     */
    public function it_can_get_by_name_case_insensitive()
    {
        $entity = $this->dbalReadRepository->getByName(
            new StringLiteral('BosWandeling')
        );

        $this->assertEquals($this->entityByName, $entity);
    }

    /**
     * @test
     */
    public function it_does_not_get_on_part_of_name()
    {
        $entity = $this->dbalReadRepository->getByName(
            new StringLiteral('oswand')
        );

        $this->assertNull($entity);
    }

    /**
     * @test
     */
    public function it_returns_null_when_not_found_by_name()
    {
        $entity = $this->dbalReadRepository->getByName(
            new StringLiteral('familievoorstelling')
        );

        $this->assertNull($entity);
    }

    /**
     * @test
     */
    public function it_can_search_on_exact_name()
    {
        $search = new Query(new StringLiteral('label1'));

        $entities = $this->dbalReadRepository->search($search);

        $this->assertEquals(1, count($entities));
    }

    /**
     * @test
     */
    public function it_can_search_on_name_part()
    {
        $search = new Query(new StringLiteral('labe'));

        $entities = $this->dbalReadRepository->search($search);

        $this->assertEquals(10, count($entities));
    }

    /**
     * @test
     */
    public function it_can_search_on_name_case_insensitive()
    {
        $search = new Query(new StringLiteral('LAB'));

        $entities = $this->dbalReadRepository->search($search);

        $this->assertEquals(10, count($entities));
    }

    /**
     * @test
     */
    public function it_can_filter_private_labels_for_user_with_missing_role()
    {
        $userId = new StringLiteral('70569052-37d5-4937-bf09-16c7a255c7d3');
        $this->seedRoles($userId);

        $search = new Query(
            new StringLiteral('wandel'),
            $userId
        );

        $entities = $this->dbalReadRepository->search($search);

        $this->assertEquals(
            [
                $this->entityByName,
                $this->entityPrivateAccess,
            ],
            $entities
        );

        $count = $this->dbalReadRepository->searchTotalLabels($search);
        $this->assertEquals(new Natural(2), $count);
    }

    /**
     * @test
     */
    public function it_can_search_with_offset()
    {
        $search = new Query(
            new StringLiteral('label'),
            null,
            new Natural(5)
        );

        $entities = $this->dbalReadRepository->search($search);

        $this->assertEquals(5, count($entities));
        $this->assertEquals('label5', $entities[0]->getName()->toNative());
        $this->assertEquals('label9', $entities[4]->getName()->toNative());
    }

    /**
     * @test
     */
    public function it_can_search_with_offset_and_limit()
    {
        $search = new Query(
            new StringLiteral('label'),
            null,
            new Natural(4),
            new Natural(3)
        );

        $entities = $this->dbalReadRepository->search($search);

        $this->assertEquals(3, count($entities));
        $this->assertEquals('label4', $entities[0]->getName()->toNative());
        $this->assertEquals('label6', $entities[2]->getName()->toNative());
    }

    /**
     * @test
     */
    public function it_can_search_with_limit()
    {
        $search = new Query(
            new StringLiteral('label'),
            null,
            null,
            new Natural(3)
        );

        $entities = $this->dbalReadRepository->search($search);

        $this->assertEquals(3, count($entities));
        $this->assertEquals('label0', $entities[0]->getName()->toNative());
        $this->assertEquals('label2', $entities[2]->getName()->toNative());
    }

    /**
     * @test
     */
    public function it_returns_null_when_nothing_matches_search()
    {
        $search = new Query(new StringLiteral('nothing_please'));

        $entities = $this->dbalReadRepository->search($search);

        $this->assertNull($entities);
    }

    /**
     * @test
     */
    public function it_can_get_total_items_of_search()
    {
        $search = new Query(new StringLiteral('lab'));

        $totalLabels = $this->dbalReadRepository->searchTotalLabels($search);

        $this->assertEquals(new Natural(10), $totalLabels);
    }

    /**
     * @test
     */
    public function it_returns_zero_for_total_items_when_search_did_match_nothing()
    {
        $search = new Query(new StringLiteral('kroegentocht'));

        $totalLabels = $this->dbalReadRepository->searchTotalLabels($search);

        $this->assertEquals(new Natural(0), $totalLabels);
    }

    /**
     * @test
     */
    public function a_new_label_can_be_used()
    {
        $this->assertTrue($this->dbalReadRepository->canUseLabel(
            new StringLiteral('0092d9eb-7f91-4699-876a-21cc660925d4'),
            new StringLiteral('fietstocht')
        ));
    }

    /**
     * @test
     */
    public function a_public_label_can_be_used()
    {
        $this->assertTrue($this->dbalReadRepository->canUseLabel(
            new StringLiteral('8d2f6739-7ba1-4c82-99f1-deca6cc79654'),
            new StringLiteral('bibliotheekweek')
        ));
    }

    /**
     * @test
     */
    public function a_user_needs_permission_on_private_label()
    {
        $userId = new StringLiteral('a02f67cb-3227-439b-861b-6ec24de7f0d1');
        $this->seedRoles($userId);

        $this->assertTrue($this->dbalReadRepository->canUseLabel(
            $userId,
            $this->entityPrivateAccess->getName()
        ));

        $this->assertFalse($this->dbalReadRepository->canUseLabel(
            $userId,
            $this->entityPrivateNoAccess->getName()
        ));
    }

    /**
     * @test
     */
    public function a_user_needs_permission_on_private_label_case_insensitive()
    {
        $userId = new StringLiteral('a02f67cb-3227-439b-861b-6ec24de7f0d1');
        $this->seedRoles($userId);

        $this->assertTrue($this->dbalReadRepository->canUseLabel(
            $userId,
            new StringLiteral('Wandeltocht')
        ));

        $this->assertFalse($this->dbalReadRepository->canUseLabel(
            $userId,
            new StringLiteral('Stadswandeling')
        ));
    }

    /**
     * @param UUID $labelId
     * @param UUID $roleId
     */
    private function insertLabelRole(UUID $labelId, UUID $roleId)
    {
        $this->getConnection()->insert(
            $this->labelRolesTableName->toNative(),
            [
                LabelRolesSchemaConfigurator::LABEL_ID_COLUMN => $labelId->toNative(),
                LabelRolesSchemaConfigurator::ROLE_ID_COLUMN => $roleId->toNative(),
            ]
        );
    }

    /**
     * @param StringLiteral $userId
     * @param UUID $roleId
     */
    private function insertUserRole(StringLiteral $userId, UUID $roleId)
    {
        $this->getConnection()->insert(
            $this->userRolesTableName->toNative(),
            [
                PermissionsSchemaConfigurator::USER_ID_COLUMN => $userId->toNative(),
                PermissionsSchemaConfigurator::ROLE_ID_COLUMN => $roleId->toNative(),
            ]
        );
    }

    /**
     * @param StringLiteral $userId
     */
    private function seedRoles(StringLiteral $userId)
    {
        $roleId1 = new UUID();
        $roleId2 = new UUID();

        $this->insertUserRole($userId, $roleId1);

        $this->insertLabelRole($this->entityPrivateAccess->getUuid(), $roleId1);

        // Also add non private labels to a role to check if duplicates are avoided.
        $this->insertLabelRole($this->entityByName->getUuid(), $roleId1);
        $this->insertLabelRole($this->entityByUuid->getUuid(), $roleId2);

        // And a private label but user has not the required role.
        $this->insertLabelRole($this->entityPrivateNoAccess->getUuid(), $roleId2);
    }
}
