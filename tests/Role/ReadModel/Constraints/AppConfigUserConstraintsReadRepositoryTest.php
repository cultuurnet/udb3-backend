<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Constraints;

use CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine\UserConstraintsReadRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AppConfigUserConstraintsReadRepositoryTest extends TestCase
{
    /**
     * @var UserConstraintsReadRepository&MockObject
     */
    private UserConstraintsReadRepository $databaseRepository;

    protected function setUp(): void
    {
        $this->databaseRepository = $this->createMock(UserConstraintsReadRepository::class);
    }

    /**
     * @test
     */
    public function it_calls_database_repository_when_it_has_no_sapi3_constraint(): void
    {
        $config = [
            'jkfhsjkfsdhjk@clients' => [
                'permissions' => [Permission::aanbodBewerken(), Permission::productiesAanmaken()],
                'labels' => ['UiTinLeuven'],
            ],
        ];
        $repository = new AppConfigUserConstraintsReadRepository($this->databaseRepository, $config);

        $this->databaseRepository->expects($this->once())
            ->method('getByUserAndPermission')
            ->willReturn(['creator:8033457c-e13e-43eb-9c24-5d03e4741f82']);

        $result = $repository->getByUserAndPermission('jkfhsjkfsdhjk@clients', Permission::aanbodBewerken());

        $expected = ['creator:8033457c-e13e-43eb-9c24-5d03e4741f82'];
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function it_calls_database_repository_when_no_permission_matches(): void
    {
        $config = [
            'jkfhsjkfsdhjk@clients' => [
                'permissions' => [Permission::aanbodBewerken(), Permission::productiesAanmaken()],
                'sapi3_constraint' => 'creator:8033457c-e13e-43eb-9c24-5d03e4741f95',
                'labels' => ['UiTinLeuven'],
            ],
        ];
        $repository = new AppConfigUserConstraintsReadRepository($this->databaseRepository, $config);

        $this->databaseRepository->expects($this->once())
            ->method('getByUserAndPermission')
            ->willReturn(['creator:8033457c-e13e-43eb-9c24-5d03e4741f82']);

        $result = $repository->getByUserAndPermission('jkfhsjkfsdhjk@clients', Permission::aanbodVerwijderen());

        $expected = ['creator:8033457c-e13e-43eb-9c24-5d03e4741f82'];
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function it_returns_the_correct_constraints_when_it_has_permission_matches_and_has_sapi3_constraint(): void
    {
        $config = [
            'jkfhsjkfsdhjk@clients' => [
                'permissions' => [Permission::aanbodBewerken(), Permission::productiesAanmaken()],
                'sapi3_constraint' => 'creator:8033457c-e13e-43eb-9c24-5d03e4741f95',
                'labels' => ['UiTinLeuven'],
            ],
        ];
        $repository = new AppConfigUserConstraintsReadRepository($this->databaseRepository, $config);

        $this->databaseRepository->expects($this->never())
            ->method('getByUserAndPermission');

        $result = $repository->getByUserAndPermission('jkfhsjkfsdhjk@clients', Permission::aanbodBewerken());

        $expected = ['creator:8033457c-e13e-43eb-9c24-5d03e4741f95'];
        $this->assertEquals($expected, $result);
    }
}
