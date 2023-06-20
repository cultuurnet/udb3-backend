<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use CultuurNet\UDB3\StringLiteral;

final class DBALWriteRepositoryTest extends BaseDBALRepositoryTest
{
    private DBALWriteRepository $dbalWriteRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbalWriteRepository = new DBALWriteRepository(
            $this->getConnection(),
            $this->getTableName()
        );
    }

    /**
     * @test
     */
    public function it_can_save(): void
    {
        $expectedEntity = new Entity(
            new UUID('a5b046e8-3e09-4929-b510-dd05752355b1'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->dbalWriteRepository->save(
            $expectedEntity->getUuid(),
            $expectedEntity->getName(),
            $expectedEntity->getVisibility(),
            $expectedEntity->getPrivacy()
        );

        $actualEntity = $this->getEntity();

        $this->assertEquals($expectedEntity, $actualEntity);
    }

    /**
     * @test
     */
    public function it_can_not_save_same_uuid(): void
    {
        $entity1 = new Entity(
            new UUID('4bf069ce-f181-4719-8a91-505d75456f1c'),
            new StringLiteral('labelName1'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->saveEntity($entity1);

        $entity2 = new Entity(
            $entity1->getUuid(),
            new StringLiteral('labelName2'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->expectException(UniqueConstraintViolationException::class);

        $this->dbalWriteRepository->save(
            $entity2->getUuid(),
            $entity2->getName(),
            $entity2->getVisibility(),
            $entity2->getPrivacy()
        );
    }

    /**
     * @test
     */
    public function it_can_not_save_same_name(): void
    {
        $entity1 = new Entity(
            new UUID('a6a4f3ca-c3e3-43d3-8589-1295284f0eef'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->saveEntity($entity1);

        $entity2 = new Entity(
            new UUID('d770db65-ca4a-4227-b540-ce060194421b'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->expectException(UniqueConstraintViolationException::class);

        $this->dbalWriteRepository->save(
            $entity2->getUuid(),
            $entity2->getName(),
            $entity2->getVisibility(),
            $entity2->getPrivacy()
        );
    }

    /**
     * @test
     */
    public function it_can_update_to_visible(): void
    {
        $entity = new Entity(
            new UUID('4ffd7b9d-3727-4b0f-851c-2f145b5af172'),
            new StringLiteral('labelName'),
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->saveEntity($entity);

        $this->dbalWriteRepository->updateVisible($entity->getUuid());

        $actualEntity = $this->getEntity();

        $this->assertEquals(
            Visibility::VISIBLE(),
            $actualEntity->getVisibility()
        );
    }

    /**
     * @test
     */
    public function it_can_update_to_invisible(): void
    {
        $entity = new Entity(
            new UUID('782bde60-cb83-4a8d-8924-50aaca123bc3'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->saveEntity($entity);

        $this->dbalWriteRepository->updateInvisible($entity->getUuid());

        $actualEntity = $this->getEntity();

        $this->assertEquals(
            Visibility::INVISIBLE(),
            $actualEntity->getVisibility()
        );
    }

    /**
     * @test
     */
    public function it_can_update_to_public(): void
    {
        $entity = new Entity(
            new UUID('92c02b85-02b5-43b1-bfb2-bc5092ae26b3'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        );

        $this->saveEntity($entity);

        $this->dbalWriteRepository->updatePrivate($entity->getUuid());

        $actualEntity = $this->getEntity();

        $this->assertEquals(
            Privacy::PRIVACY_PRIVATE(),
            $actualEntity->getPrivacy()
        );
    }

    /**
     * @test
     */
    public function it_can_update_to_private(): void
    {
        $entity = new Entity(
            new UUID('608f3b20-0ecc-41f6-a2fb-e59410750b37'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->saveEntity($entity);

        $this->dbalWriteRepository->updatePublic($entity->getUuid());

        $actualEntity = $this->getEntity();

        $this->assertEquals(
            Privacy::PRIVACY_PUBLIC(),
            $actualEntity->getPrivacy()
        );
    }

    /**
     * @test
     */
    public function it_can_update_to_excluded(): void
    {
        $entity = new Entity(
            new UUID('608f3b20-0ecc-41f6-a2fb-e59410750b37'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->saveEntity($entity);

        $this->dbalWriteRepository->updateExcluded($entity->getUuid());

        $actualEntity = $this->getEntity();

        $this->assertTrue($actualEntity->isExcluded());
    }

    /**
     * @test
     */
    public function it_can_update_to_included(): void
    {
        $entity = new Entity(
            new UUID('608f3b20-0ecc-41f6-a2fb-e59410750b37'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC(),
            true
        );

        $this->saveEntity($entity);

        $this->dbalWriteRepository->updateIncluded($entity->getUuid());

        $actualEntity = $this->getEntity();

        $this->assertFalse($actualEntity->isExcluded());
    }
}
