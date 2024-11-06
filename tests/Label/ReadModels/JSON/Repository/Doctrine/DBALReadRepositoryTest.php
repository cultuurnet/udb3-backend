<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine\ColumnNames as LabelRolesColumnNames;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\ColumnNames as PermissionsColumnNames;

final class DBALReadRepositoryTest extends BaseDBALRepositoryTest
{
    private DBALReadRepository $dbalReadRepository;

    private Entity $entityByUuid;

    private Entity $entityByName;

    private Entity $entityPrivateAccess;

    private Entity $entityPrivateNoAccess;

    private Entity $excluded;

    private Entity $similarEntity;

    private string $labelRolesTableName;

    private string $userRolesTableName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->labelRolesTableName = 'label_roles';
        $this->userRolesTableName = 'user_roles';

        $this->dbalReadRepository = new DBALReadRepository(
            $this->getConnection(),
            $this->getTableName(),
            $this->labelRolesTableName,
            $this->userRolesTableName
        );

        $this->entityByUuid = new Entity(
            new UUID('7f328086-0e56-4c7d-a2e7-38ac5eaa0347'),
            'bibliotheekweek',
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->entityByName = new Entity(
            new UUID('25ea383c-b14d-4776-989c-24e0ac044638'),
            'boswandeling',
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->entityPrivateAccess = new Entity(
            new UUID('6639d6d2-ac7d-4995-91e3-7660c74cf1eb'),
            'wandeltocht',
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        );

        $this->entityPrivateNoAccess = new Entity(
            new UUID('b14dd3ea-6962-4565-91b6-d0e8d929e685'),
            'stadswandeling',
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        );

        $this->similarEntity = new Entity(
            new UUID('22ce5549-4546-4a08-b036-a2c07ca4929c'),
            'wandel',
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->excluded = new Entity(
            new UUID('67dcd2a0-5301-4747-a956-3741420efd52'),
            'excluded',
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC(),
            true
        );

        /** @var Entity[] $entities */
        $entities = [$this->excluded, $this->entityPrivateAccess, $this->entityPrivateNoAccess, $this->entityByUuid, $this->entityByName, $this->similarEntity];
        foreach ($entities as $entity) {
            $this->saveEntity($entity);
        }

        for ($i = 0; $i < 10; $i++) {
            $entity = new Entity(
                new UUID('15c8c391-724d-4878-8a06-86163ed5412' . $i),
                'label' . $i,
                Visibility::VISIBLE(),
                Privacy::PRIVACY_PUBLIC()
            );
            $this->saveEntity($entity);
        }
    }

    /**
     * @test
     */
    public function it_can_get_by_uuid(): void
    {
        $entity = $this->dbalReadRepository->getByUuid(
            $this->entityByUuid->getUuid()
        );

        $this->assertEquals($this->entityByUuid, $entity);
    }

    /**
     * @test
     */
    public function it_returns_null_when_not_found_by_uuid(): void
    {
        $entity = $this->dbalReadRepository->getByUuid(
            new UUID('d8d9737f-c31e-4a5d-bc11-8780a23fdb24')
        );

        $this->assertNull($entity);
    }

    /**
     * @test
     */
    public function it_can_get_by_name(): void
    {
        $entity = $this->dbalReadRepository->getByName(
            $this->entityByName->getName()
        );

        $this->assertEquals($this->entityByName, $entity);
    }

    /**
     * @test
     */
    public function it_can_get_by_name_case_insensitive(): void
    {
        $entity = $this->dbalReadRepository->getByName('BosWandeling');

        $this->assertEquals($this->entityByName, $entity);
    }

    /**
     * @test
     */
    public function it_does_not_get_on_part_of_name(): void
    {
        $entity = $this->dbalReadRepository->getByName('oswand');

        $this->assertNull($entity);
    }

    /**
     * @test
     */
    public function it_returns_null_when_not_found_by_name(): void
    {
        $entity = $this->dbalReadRepository->getByName('familievoorstelling');

        $this->assertNull($entity);
    }

    /**
     * @test
     */
    public function it_can_search_on_exact_name(): void
    {
        $search = new Query('label1');

        $entities = $this->dbalReadRepository->search($search);

        $this->assertCount(1, $entities);
    }

    /**
     * @test
     */
    public function it_can_search_on_name_part(): void
    {
        $search = new Query('labe');

        $entities = $this->dbalReadRepository->search($search);

        $this->assertCount(10, $entities);
    }

    /**
     * @test
     */
    public function it_can_search_on_name_case_insensitive(): void
    {
        $search = new Query('LAB');

        $entities = $this->dbalReadRepository->search($search);

        $this->assertCount(10, $entities);
    }

    /**
     * @test
     */
    public function it_can_filter_private_labels_for_user_with_missing_role(): void
    {
        $userId = '70569052-37d5-4937-bf09-16c7a255c7d3';
        $this->seedRoles($userId);

        $search = new Query(
            'wandel',
            $userId
        );

        $entities = $this->dbalReadRepository->search($search);

        $this->assertEquals(
            [
                $this->similarEntity,
                $this->entityByName,
                $this->entityPrivateAccess,
            ],
            $entities
        );

        $count = $this->dbalReadRepository->searchTotalLabels($search);
        $this->assertEquals(3, $count);
    }

    /**
     * @test
     */
    public function it_can_search_with_offset(): void
    {
        $search = new Query(
            'label',
            null,
            5
        );

        $entities = $this->dbalReadRepository->search($search);

        $this->assertCount(5, $entities);
        $this->assertEquals('label5', $entities[0]->getName());
        $this->assertEquals('label9', $entities[4]->getName());
    }

    /**
     * @test
     */
    public function it_can_search_with_offset_and_limit(): void
    {
        $search = new Query(
            'label',
            null,
            4,
            3
        );

        $entities = $this->dbalReadRepository->search($search);

        $this->assertCount(3, $entities);
        $this->assertEquals('label4', $entities[0]->getName());
        $this->assertEquals('label6', $entities[2]->getName());
    }

    /**
     * @test
     */
    public function it_can_search_with_limit(): void
    {
        $search = new Query(
            'label',
            null,
            null,
            3
        );

        $entities = $this->dbalReadRepository->search($search);

        $this->assertCount(3, $entities);
        $this->assertEquals('label0', $entities[0]->getName());
        $this->assertEquals('label2', $entities[2]->getName());
    }

    /**
     * @test
     */
    public function it_returns_an_empty_array_when_nothing_matches_search(): void
    {
        $search = new Query('nothing_please');
        $entities = $this->dbalReadRepository->search($search);
        $this->assertEquals([], $entities);
    }

    /**
     * @test
     */
    public function it_does_not_filter_excluded_labels_if_suggestions_is_false(): void
    {
        $search = new Query(
            'excluded',
            null,
            null,
            null,
            false
        );
        $entities = $this->dbalReadRepository->search($search);
        $this->assertEquals('excluded', $entities[0]->getName());
    }

    /**
     * @test
     */
    public function it_filters_excluded_labels_if_suggestions_is_true(): void
    {
        $search = new Query(
            'excluded',
            null,
            null,
            null,
            true
        );
        $entities = $this->dbalReadRepository->search($search);
        $this->assertEquals([], $entities);
    }

    /**
     * @test
     */
    public function it_sorts_labels_by_similarity(): void
    {
        $search = new Query(
            'wandel',
            null,
            null,
            6
        );

        $entities = $this->dbalReadRepository->search($search);

        $this->assertCount(4, $entities);
        $this->assertEquals('wandel', $entities[0]->getName());
        $this->assertEquals('wandeltocht', $entities[1]->getName());
        $this->assertEquals('boswandeling', $entities[2]->getName());
        $this->assertEquals('stadswandeling', $entities[3]->getName());
    }

    /**
     * @test
     */
    public function it_can_get_total_items_of_search(): void
    {
        $search = new Query('lab');

        $totalLabels = $this->dbalReadRepository->searchTotalLabels($search);

        $this->assertEquals(10, $totalLabels);
    }

    /**
     * @test
     */
    public function it_returns_zero_for_total_items_when_search_did_match_nothing(): void
    {
        $search = new Query('kroegentocht');

        $totalLabels = $this->dbalReadRepository->searchTotalLabels($search);

        $this->assertEquals(0, $totalLabels);
    }

    /**
     * @test
     */
    public function a_new_label_can_be_used(): void
    {
        $this->assertTrue($this->dbalReadRepository->canUseLabel(
            '0092d9eb-7f91-4699-876a-21cc660925d4',
            'fietstocht'
        ));
    }

    /**
     * @test
     */
    public function a_public_label_can_be_used(): void
    {
        $this->assertTrue($this->dbalReadRepository->canUseLabel(
            '8d2f6739-7ba1-4c82-99f1-deca6cc79654',
            'bibliotheekweek'
        ));
    }

    /**
     * @test
     */
    public function a_user_needs_permission_on_private_label(): void
    {
        $userId = 'a02f67cb-3227-439b-861b-6ec24de7f0d1';
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
    public function a_user_needs_permission_on_private_label_case_insensitive(): void
    {
        $userId = 'a02f67cb-3227-439b-861b-6ec24de7f0d1';
        $this->seedRoles($userId);

        $this->assertTrue($this->dbalReadRepository->canUseLabel(
            $userId,
            'Wandeltocht'
        ));

        $this->assertFalse($this->dbalReadRepository->canUseLabel(
            $userId,
            'Stadswandeling'
        ));
    }

    /**
     * @test
     */
    public function it_takes_into_account_excluded(): void
    {
        $excludedLabel = $this->dbalReadRepository->getByName('excluded');
        $this->assertEquals(
            $this->excluded,
            $excludedLabel
        );
    }

    private function insertLabelRole(UUID $labelId, UUID $roleId): void
    {
        $this->getConnection()->insert(
            $this->labelRolesTableName,
            [
                LabelRolesColumnNames::LABEL_ID_COLUMN => $labelId->toString(),
                LabelRolesColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );
    }


    private function insertUserRole(string $userId, UUID $roleId): void
    {
        $this->getConnection()->insert(
            $this->userRolesTableName,
            [
                PermissionsColumnNames::USER_ID_COLUMN => $userId,
                PermissionsColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );
    }


    private function seedRoles(string $userId): void
    {
        $roleId1 = new UUID('5d0842b4-4fd1-4bc2-8577-c06a5ac5000a');
        $roleId2 = new UUID('56a8b820-2262-4a17-a496-bfa07f7e49bb');

        $this->insertUserRole($userId, $roleId1);

        $this->insertLabelRole($this->entityPrivateAccess->getUuid(), $roleId1);

        // Also add non private labels to a role to check if duplicates are avoided.
        $this->insertLabelRole($this->entityByName->getUuid(), $roleId1);
        $this->insertLabelRole($this->entityByUuid->getUuid(), $roleId2);

        // And a private label but user has not the required role.
        $this->insertLabelRole($this->entityPrivateNoAccess->getUuid(), $roleId2);
    }
}
