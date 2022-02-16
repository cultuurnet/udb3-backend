<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use ValueObjects\StringLiteral\StringLiteral;

class DBALWriteRepositoryTest extends BaseDBALRepositoryTest
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
            Privacy::PRIVACY_PUBLIC(),
            new UUID('1d3debd6-3627-4c5c-b3e3-9c140d041d57')
        );

        $this->dbalWriteRepository->save(
            $expectedEntity->getUuid(),
            $expectedEntity->getName(),
            $expectedEntity->getVisibility(),
            $expectedEntity->getPrivacy(),
            $expectedEntity->getParentUuid()
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
            Privacy::PRIVACY_PUBLIC(),
            new UUID('7ebb572b-538f-4b0c-8e5c-cc6bb7e73f6c')
        );

        $this->saveEntity($entity1);

        $entity2 = new Entity(
            $entity1->getUuid(),
            new StringLiteral('labelName2'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC(),
            new UUID('a8d59c75-3202-4ffe-b973-3079acd65641')
        );

        $this->expectException(UniqueConstraintViolationException::class);

        $this->dbalWriteRepository->save(
            $entity2->getUuid(),
            $entity2->getName(),
            $entity2->getVisibility(),
            $entity2->getPrivacy(),
            $entity2->getParentUuid()
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
            Privacy::PRIVACY_PUBLIC(),
            new UUID('ad29d376-531b-4084-a5c6-66b77bccf3b6')
        );

        $this->saveEntity($entity1);

        $entity2 = new Entity(
            new UUID('d770db65-ca4a-4227-b540-ce060194421b'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC(),
            new UUID('1f1a0583-0f82-454e-a9bc-c5e4b96db59e')
        );

        $this->expectException(UniqueConstraintViolationException::class);

        $this->dbalWriteRepository->save(
            $entity2->getUuid(),
            $entity2->getName(),
            $entity2->getVisibility(),
            $entity2->getPrivacy(),
            $entity2->getParentUuid()
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
            Privacy::PRIVACY_PUBLIC(),
            new UUID('4e897a14-a108-4fd2-a4b8-65e80ef2b2db')
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
            Privacy::PRIVACY_PUBLIC(),
            new UUID('e7b53de8-1e9d-47f6-903b-d0d9f03af35a')
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
            Privacy::PRIVACY_PRIVATE(),
            new UUID('90cf68f8-8b32-4999-ab83-4fde5e9e99a9')
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
            Privacy::PRIVACY_PUBLIC(),
            new UUID('d7eb2b24-8910-4578-a644-2c81b55f03b4')
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
    public function it_can_increment(): void
    {
        $expectedEntity = new Entity(
            new UUID('df9e1cd0-60f4-4c5d-abb8-d381a93b2c13'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC(),
            new UUID('b0c2993b-8987-4c95-8723-57611ab68f3f'),
            666
        );

        $this->saveEntity($expectedEntity);

        $this->dbalWriteRepository->updateCountIncrement(
            $expectedEntity->getUuid()
        );

        $actualEntity = $this->getEntity();

        $this->assertEquals(
            667,
            $actualEntity->getCount()
        );
    }

    /**
     * @test
     */
    public function it_can_decrement(): void
    {
        $expectedEntity = new Entity(
            new UUID('5d9b7e42-b841-4d92-a5ab-7623f3518bb0'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC(),
            new UUID('f12fc176-b66f-4c0c-a231-0c0f15499e0a'),
            666
        );

        $this->saveEntity($expectedEntity);

        $this->dbalWriteRepository->updateCountDecrement(
            $expectedEntity->getUuid()
        );

        $actualEntity = $this->getEntity();

        $this->assertEquals(
            665,
            $actualEntity->getCount()
        );
    }

    /**
     * @test
     */
    public function count_never_smaller_then_zero(): void
    {
        $expectedEntity = new Entity(
            new UUID('044a810b-a905-4f6b-88ff-df8bf5508e6c'),
            new StringLiteral('labelName'),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC(),
            new UUID('48cbbee1-e6d1-4f63-8800-a51e6f55a8d3'),
            0
        );

        $this->saveEntity($expectedEntity);

        $this->dbalWriteRepository->updateCountDecrement(
            $expectedEntity->getUuid()
        );

        $actualEntity = $this->getEntity();

        $this->assertEquals(
            0,
            $actualEntity->getCount()
        );
    }
}
