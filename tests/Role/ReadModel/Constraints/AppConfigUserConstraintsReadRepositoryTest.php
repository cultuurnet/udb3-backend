<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Constraints;

use CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine\UserConstraintsReadRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\StringLiteral;
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
    public function it_calls_database_repository_when_it_has_no_sapi3_constraint() {
        $config = [
            'jkfhsjkfsdhjk@clients' => [
                'permissions' => [Permission::aanbodBewerken(), Permission::productiesAanmaken()],
                'labels' => ['UiTinLeuven'],
            ]
        ];
        $repository = new AppConfigUserConstraintsReadRepository($this->databaseRepository, $config);

        $this->databaseRepository->expects($this->once())
            ->method('getByUserAndPermission')
            ->willReturn(['creator:8033457c-e13e-43eb-9c24-5d03e4741f82']);

        $result = $repository->getByUserAndPermission(new StringLiteral('jkfhsjkfsdhjk@clients'), Permission::aanbodBewerken());

        $expected = ['creator:8033457c-e13e-43eb-9c24-5d03e4741f82'];
        $this->assertEquals($expected, $result);
    }
    }

}
